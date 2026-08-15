<?php

use App\Enums\TeamRole;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a personal team sees no federation stats on the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('stats', null));
});

test('a real federation sees aggregated stats on the dashboard', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);

    $ranking = Ranking::create(['owner_team_id' => $team->id, 'name' => 'R', 'acronym' => 'R']);
    $player = Player::create(['name' => 'Ana', 'last_name' => 'García']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $player->id, 'id_on_ranking' => 1]);

    $team->tournaments()->create([
        'name' => 'Torneo del mes', 'speed' => 'blitz', 'begins' => now()->startOfMonth()->addDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.rankingsCount', 1)
            ->where('stats.playersCount', 1)
            ->where('stats.tournamentsThisMonth', 1),
        );
});
