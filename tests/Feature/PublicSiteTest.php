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

test('the public site exposes rankings, players, tournaments and competitions without authentication', function () {
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

    $competition = $team->competitions()->create(['name' => 'Circuito Público', 'url' => 'circuito-publico', 'speed' => 'standard']);

    $path = sys_get_temp_dir().'/trf16_public_site.trf';
    (new Trf16Builder)
        ->header(name: 'Torneo Público', players: 2, playersRank: 2)
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

    $tournament = Tournament::where('name', 'Torneo Público')->first();

    $this->actingAs($user)
        ->post(route('tournaments.process', ['current_team' => $team->slug, 'tournament' => $tournament->id]), [
            'speed' => 'standard',
            'competition_id' => $competition->id,
        ])
        ->assertRedirect();

    $tournament->refresh();
    unlink($path);

    // --- Public site, no auth ---

    $this->get(route('public.rankings.index', ['team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/rankings/Index')->has('rankings', 1));

    $this->get(route('public.rankings.show', ['team' => $team->slug, 'ranking' => $ranking->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/rankings/Show')->has('players.data', 2));

    $this->get(route('public.players.index', ['team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/players/Index')->has('players.data', 2));

    $this->get(route('public.players.show', ['team' => $team->slug, 'player' => $garcia->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/players/Show')
            ->has('rankings', 1)
            ->has('tournaments', 1));

    $this->get(route('public.tournaments.index', ['team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/tournaments/Index')->has('tournaments.data', 1));

    $this->get(route('public.tournaments.show', ['team' => $team->slug, 'tournament' => $tournament->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/tournaments/Show')
            ->where('competition', null)
            ->has('standings.data', 2));

    $this->get(route('public.tournaments.player', ['team' => $team->slug, 'tournament' => $tournament->id, 'player' => $garcia->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/tournaments/Player')
            ->has('games', 1)
            ->where('games.0.score', 1)
            ->where('games.0.color', 'white'));

    $this->get(route('public.competitions.index', ['team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/competitions/Index')->has('competitions.data', 1));

    $this->get(route('public.competitions.show', ['team' => $team->slug, 'competition' => $competition->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/competitions/Show')
            ->has('standings.data', 2)
            ->has('tournaments', 1));

    // The competition-scoped tournament routes reuse the SAME controller
    // action/component as the plain ones — this is the fix for the 1.0
    // near-duplicate-page finding (7.3).
    $this->get(route('public.competitions.tournaments.show', ['team' => $team->slug, 'competition' => $competition->id, 'tournament' => $tournament->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/tournaments/Show')
            ->where('competition.id', $competition->id));

    $this->get(route('public.competitions.tournaments.player', [
        'team' => $team->slug, 'competition' => $competition->id, 'tournament' => $tournament->id, 'player' => $garcia->id,
    ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/tournaments/Player')
            ->where('competition.id', $competition->id));
});

test('the public site 404s for a tournament that is not yet processed', function () {
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $tournament = $team->tournaments()->create(['name' => 'Sin procesar', 'speed' => 'blitz', 'processed' => false]);

    $this->get(route('public.tournaments.show', ['team' => $team->slug, 'tournament' => $tournament->id]))
        ->assertNotFound();
});

test('the public site 404s when a tournament does not belong to the competition in the URL', function () {
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $tournament = $team->tournaments()->create(['name' => 'Torneo', 'speed' => 'blitz', 'processed' => true]);
    $otherCompetition = $team->competitions()->create(['name' => 'Otra', 'url' => 'otra', 'speed' => 'blitz']);

    $this->get(route('public.competitions.tournaments.show', [
        'team' => $team->slug, 'competition' => $otherCompetition->id, 'tournament' => $tournament->id,
    ]))->assertNotFound();
});
