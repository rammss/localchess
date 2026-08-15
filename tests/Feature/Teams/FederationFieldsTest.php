<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('a team owner can update the federation-specific fields', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);

    $this->actingAs($user)
        ->patch(route('teams.update', ['team' => $team->slug]), [
            'name' => 'Federación de Prueba',
            'acronym' => 'FDP',
            'city' => 'Mar del Plata',
            'state' => 'Buenos Aires',
            'country' => 'Argentina',
            'address' => 'Moreno 3440',
            'website' => 'https://www.example.com',
            'email' => 'contacto@example.com',
            'phone' => '+54 223 555 0000',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'acronym' => 'FDP',
        'city' => 'Mar del Plata',
        'email' => 'contacto@example.com',
    ]);
});
