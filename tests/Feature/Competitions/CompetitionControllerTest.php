<?php

use App\Enums\TeamRole;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;

test('a federation owner can create, view and update a competition', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);

    $this->actingAs($user)
        ->post(route('competitions.store', ['current_team' => $team->slug]), [
            'name' => 'Circuito Apertura',
            'url' => 'circuito-apertura',
            'speed' => 'blitz',
            'discarded_games' => 3,
        ])
        ->assertRedirect();

    $competition = Competition::where('url', 'circuito-apertura')->first();
    expect($competition)->not->toBeNull()
        ->and($competition->team_id)->toBe($team->id);

    $this->actingAs($user)
        ->get(route('competitions.show', ['current_team' => $team->slug, 'competition' => $competition->id]))
        ->assertOk();

    $this->actingAs($user)
        ->patch(route('competitions.update', ['current_team' => $team->slug, 'competition' => $competition->id]), [
            'name' => 'Circuito Apertura 2026',
            'url' => 'circuito-apertura',
            'speed' => 'blitz',
            'discarded_games' => 2,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'name' => 'Circuito Apertura 2026']);
});

test('staff of another federation cannot view a competition that is not theirs', function () {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Federación A', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $owner->id, 'role' => TeamRole::Owner]);
    $competition = $team->competitions()->create(['name' => 'X', 'url' => 'x', 'speed' => 'blitz']);

    $outsider = User::factory()->create();
    $otherTeam = Team::create(['name' => 'Federación B', 'is_personal' => false]);
    $otherTeam->memberships()->create(['user_id' => $outsider->id, 'role' => TeamRole::Owner]);

    $this->actingAs($outsider)
        ->get(route('competitions.show', ['current_team' => $otherTeam->slug, 'competition' => $competition->id]))
        ->assertForbidden();
});
