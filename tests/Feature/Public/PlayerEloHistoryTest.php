<?php

use App\Enums\TeamRole;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\ResponseFactory;
use Tests\Fixtures\Trf16Builder;

test('the elo history chart data is omitted from the initial load and resolved on a deferred partial reload', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Admin]);

    $ranking = Ranking::create([
        'owner_team_id' => $team->id, 'name' => 'Ranking', 'acronym' => 'R',
        'ranking_base' => 1400, 'ranking_restart' => 1000, 'ranking_max_entry' => 2200,
        'valuation_games_standard' => 5, 'k_base' => 40, 'last_used_id' => 200,
        'k_rules' => ['rank_category' => false, 'rank_underage' => false], 'state' => true,
    ]);

    $garcia = Player::create(['name' => 'Ana', 'last_name' => 'García']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $garcia->id, 'id_on_ranking' => 101, 'standard_elo' => 1800, 'k_standard_elo' => 20]);
    $rival = Player::create(['name' => 'Bruno', 'last_name' => 'López']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $rival->id, 'id_on_ranking' => 102, 'standard_elo' => 1700, 'k_standard_elo' => 20]);

    $path = sys_get_temp_dir().'/trf16_elo_history.trf';
    (new Trf16Builder)
        ->header(name: 'Torneo Historial', players: 2, playersRank: 2)
        ->rounds(['2026/03/01'])
        ->player(1, 'García', 'Ana', 1800, 101, points: 1.0, rank: 1, games: [
            ['rival' => 2, 'color' => 'w', 'result' => '1'],
        ])
        ->player(2, 'López', 'Bruno', 1700, 102, points: 0.0, rank: 2, games: [
            ['rival' => 1, 'color' => 'b', 'result' => '0'],
        ])
        ->writeTo($path);

    $this->actingAs($user)->post(route('tournaments.store', ['current_team' => $team->slug]), [
        'file' => new UploadedFile($path, 'torneo.trf', 'text/plain', null, true),
        'ranking_id' => $ranking->id,
    ])->assertRedirect();

    $tournament = Tournament::where('name', 'Torneo Historial')->first();

    $this->actingAs($user)
        ->post(route('tournaments.process', ['current_team' => $team->slug, 'tournament' => $tournament->id]), ['speed' => 'standard'])
        ->assertRedirect();

    unlink($path);

    // Initial (non-deferred) load: eloHistory must NOT be present.
    $this->get(route('public.players.show', ['team' => $team->slug, 'player' => $garcia->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/players/Show')
            ->missing('eloHistory'));

    $version = app(ResponseFactory::class)->getVersion();

    // Deferred partial reload: eloHistory resolves with the real data. This
    // is a raw X-Inertia JSON response (not a full page), so it's asserted
    // directly rather than via assertInertia() (which expects a Blade view).
    $response = $this->get(route('public.players.show', ['team' => $team->slug, 'player' => $garcia->id]), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'public/players/Show',
        'X-Inertia-Partial-Data' => 'eloHistory',
    ]);

    $response->assertOk();
    $eloHistory = $response->json('props.eloHistory');

    expect($eloHistory)->toHaveCount(1)
        ->and($eloHistory[0]['type'])->toBe('standard')
        ->and($eloHistory[0]['date'])->toBe('2026-01-11')
        ->and($eloHistory[0]['tournament'])->toBe('Torneo Historial');
});
