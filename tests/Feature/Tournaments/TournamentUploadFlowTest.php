<?php

use App\Enums\TeamRole;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\Fixtures\Trf16Builder;

test('an admin can upload a TRF16 file, confirm it, and process it end to end', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Admin]);

    $ranking = Ranking::create([
        'owner_team_id' => $team->id, 'name' => 'Ranking', 'acronym' => 'R',
        'ranking_base' => 1400, 'ranking_restart' => 1000, 'ranking_max_entry' => 2200,
        'valuation_games_standard' => 5, 'k_base' => 40, 'last_used_id' => 200,
        'k_rules' => ['rank_category' => false, 'rank_underage' => false],
    ]);

    $garcia = Player::create(['name' => 'Ana', 'last_name' => 'García']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $garcia->id, 'id_on_ranking' => 101, 'standard_elo' => 1800, 'k_standard_elo' => 20]);
    $rival = Player::create(['name' => 'Bruno', 'last_name' => 'López']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $rival->id, 'id_on_ranking' => 102, 'standard_elo' => 1700, 'k_standard_elo' => 20]);

    $path = sys_get_temp_dir().'/trf16_upload_flow.trf';
    (new Trf16Builder)
        ->header(name: 'Torneo Subido', players: 3, playersRank: 3)
        ->rounds(['2026/03/01'])
        ->player(1, 'García', 'Ana', 1800, 101, points: 1.0, rank: 1, games: [
            ['rival' => 2, 'color' => 'w', 'result' => '1'],
        ])
        ->player(2, 'López', 'Bruno', 1700, 102, points: 0.0, rank: 2, games: [
            ['rival' => 1, 'color' => 'b', 'result' => '0'],
        ])
        ->player(3, 'Nuevo', 'Pedro', 0, 999, points: 0.0, rank: 3, games: [
            ['rival' => 0, 'color' => null, 'result' => 'Z'],
        ])
        ->writeTo($path);

    // 1. Upload.
    $response = $this->actingAs($user)->post(route('tournaments.store', ['current_team' => $team->slug]), [
        'file' => new UploadedFile($path, 'torneo.trf', 'text/plain', null, true),
        'ranking_id' => $ranking->id,
    ]);
    $response->assertRedirect();

    $tournament = Tournament::where('name', 'Torneo Subido')->first();
    expect($tournament)->not->toBeNull()
        ->and($tournament->processed)->toBeFalse()
        ->and($tournament->data)->not->toBeNull();

    // 2. Process (skips the confirm GET page render, which needs the Vue
    // component — the domain-level effect is what matters for this test).
    $this->actingAs($user)
        ->post(route('tournaments.process', ['current_team' => $team->slug, 'tournament' => $tournament->id]), [
            'speed' => 'standard',
        ])
        ->assertRedirect();

    $tournament->refresh();
    expect($tournament->processed)->toBeTrue()
        ->and($tournament->amount_games)->toBe(2);

    $newPlayer = Player::where('last_name', 'Nuevo')->first();
    expect($newPlayer)->not->toBeNull();

    $garciaRanking = PlayerRanking::where('ranking_id', $ranking->id)->where('player_id', $garcia->id)->first();
    expect((float) $garciaRanking->standard_elo)->toBeGreaterThan(1800.0);

    unlink($path);
});

test('a member without admin rights cannot upload a tournament', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);
    $ranking = Ranking::create(['owner_team_id' => $team->id, 'name' => 'R', 'acronym' => 'R']);

    $path = sys_get_temp_dir().'/trf16_forbidden.trf';
    (new Trf16Builder)->header()->rounds(['2026/03/01'])->writeTo($path);

    $this->actingAs($user)
        ->post(route('tournaments.store', ['current_team' => $team->slug]), [
            'file' => new UploadedFile($path, 'torneo.trf', 'text/plain', null, true),
            'ranking_id' => $ranking->id,
        ])
        ->assertForbidden();

    unlink($path);
});
