<?php

use App\Enums\TeamRole;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;

test('staff of a federation can create, edit and delete a player', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Admin]);

    $this->actingAs($user)
        ->post(route('players.store', ['current_team' => $team->slug]), [
            'name' => 'Ana', 'last_name' => 'García',
        ])
        ->assertRedirect();

    $player = Player::where('last_name', 'García')->first();
    expect($player)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('players.update', ['current_team' => $team->slug, 'player' => $player->id]), [
            'name' => 'Ana María', 'last_name' => 'García',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('players', ['id' => $player->id, 'name' => 'Ana María']);

    $this->actingAs($user)
        ->delete(route('players.destroy', ['current_team' => $team->slug, 'player' => $player->id]))
        ->assertRedirect();

    $this->assertSoftDeleted('players', ['id' => $player->id]);
});

test('a casual user with only a personal team cannot manage players', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    $this->actingAs($user)
        ->post(route('players.store', ['current_team' => $personalTeam->slug]), ['name' => 'X'])
        ->assertForbidden();
});
