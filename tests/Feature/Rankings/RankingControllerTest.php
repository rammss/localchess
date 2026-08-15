<?php

use App\Enums\TeamRole;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\User;

function makeFederationWithOwner(): array
{
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);
    $user->switchTeam($team);

    return [$user, $team];
}

test('a federation owner can create, edit and delete a ranking', function () {
    [$user, $team] = makeFederationWithOwner();

    $response = $this->actingAs($user)->post(route('rankings.store', ['current_team' => $team->slug]), [
        'name' => 'Ranking Oficial',
        'acronym' => 'RO',
        'is_default' => true,
        'state' => true,
        'ranking_base' => 1400,
        'ranking_restart' => 1000,
        'ranking_max_entry' => 2200,
        'k_base' => 40,
        'k_blitz' => 0.6,
        'k_rapid' => 0.8,
        'valuation_games_standard' => 5,
        'valuation_games_rapid' => 5,
        'valuation_games_blitz' => 5,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('rankings', ['name' => 'Ranking Oficial', 'owner_team_id' => $team->id]);

    $ranking = Ranking::where('name', 'Ranking Oficial')->first();

    $this->actingAs($user)
        ->patch(route('rankings.update', ['current_team' => $team->slug, 'ranking' => $ranking->id]), [
            'name' => 'Ranking Oficial Actualizado',
            'acronym' => 'ROA',
            'ranking_base' => 1400,
            'ranking_restart' => 1000,
            'ranking_max_entry' => 2200,
            'k_base' => 40,
            'k_blitz' => 0.6,
            'k_rapid' => 0.8,
            'valuation_games_standard' => 5,
            'valuation_games_rapid' => 5,
            'valuation_games_blitz' => 5,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('rankings', ['id' => $ranking->id, 'name' => 'Ranking Oficial Actualizado']);

    $this->actingAs($user)
        ->delete(route('rankings.destroy', ['current_team' => $team->slug, 'ranking' => $ranking->id]))
        ->assertRedirect();

    $this->assertSoftDeleted('rankings', ['id' => $ranking->id]);
});

test('checkbox fields submitted the way a real browser sends them ("on" when checked, absent when not) are accepted', function () {
    // Regression test: a native checkbox with no explicit `value` submits
    // the literal string "on", which Laravel's plain `boolean` rule
    // rejects outright — caught via a real browser click-through, not the
    // controlled-boolean payloads the other tests in this file send.
    [$user, $team] = makeFederationWithOwner();

    $this->actingAs($user)->post(route('rankings.store', ['current_team' => $team->slug]), [
        'name' => 'Ranking Oficial',
        'acronym' => 'RO',
        'is_default' => 'on',
        'state' => 'on',
        'ranking_base' => 1400,
        'ranking_restart' => 1000,
        'ranking_max_entry' => 2200,
        'k_base' => 40,
        'k_blitz' => 0.6,
        'k_rapid' => 0.8,
        'valuation_games_standard' => 5,
        'valuation_games_rapid' => 5,
        'valuation_games_blitz' => 5,
    ])->assertRedirect();

    $this->assertDatabaseHas('rankings', ['name' => 'Ranking Oficial', 'is_default' => true, 'state' => true]);

    // Unchecked checkboxes are simply absent from the payload.
    $this->actingAs($user)->post(route('rankings.store', ['current_team' => $team->slug]), [
        'name' => 'Ranking Secundario',
        'acronym' => 'RS',
        'ranking_base' => 1400,
        'ranking_restart' => 1000,
        'ranking_max_entry' => 2200,
        'k_base' => 40,
        'k_blitz' => 0.6,
        'k_rapid' => 0.8,
        'valuation_games_standard' => 5,
        'valuation_games_rapid' => 5,
        'valuation_games_blitz' => 5,
    ])->assertRedirect();

    $this->assertDatabaseHas('rankings', ['name' => 'Ranking Secundario', 'is_default' => false, 'state' => false]);
});

test('decimal fields sent with a comma (Spanish-locale number inputs) are accepted', function () {
    [$user, $team] = makeFederationWithOwner();

    $this->actingAs($user)->post(route('rankings.store', ['current_team' => $team->slug]), [
        'name' => 'Ranking Comas',
        'acronym' => 'RC',
        'is_default' => 'on',
        'ranking_base' => 1400,
        'ranking_restart' => 1000,
        'ranking_max_entry' => 2200,
        'k_base' => 40,
        'k_blitz' => '0,6',
        'k_rapid' => '0,8',
        'valuation_games_standard' => 5,
        'valuation_games_rapid' => 5,
        'valuation_games_blitz' => 5,
    ])->assertRedirect();

    $this->assertDatabaseHas('rankings', ['name' => 'Ranking Comas', 'k_blitz' => 0.6, 'k_rapid' => 0.8]);
});

test('a plain member cannot create a ranking', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);

    $this->actingAs($user)
        ->post(route('rankings.store', ['current_team' => $team->slug]), ['name' => 'X', 'acronym' => 'X'])
        ->assertForbidden();
});

test('a user cannot manage rankings on their personal team', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    $this->actingAs($user)
        ->get(route('rankings.create', ['current_team' => $personalTeam->slug]))
        ->assertForbidden();
});

test('a non-member cannot even reach the rankings index of a federation', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Otra Federación', 'is_personal' => false]);

    $this->actingAs($user)
        ->get(route('rankings.index', ['current_team' => $team->slug]))
        ->assertForbidden();
});
