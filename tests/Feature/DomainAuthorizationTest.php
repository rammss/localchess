<?php

use App\Enums\TeamRole;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

/**
 * This is the regression test for the single most important finding of the
 * 1.0 audit: any registered user could reach and modify every federation's
 * data. In 2.0, a personal team (auto-created for every new registrant)
 * must never grant any chess-management capability, and staff of one
 * federation must never be able to manage another's data.
 */
function makeFederation(string $name = 'Federación Marplatense'): Team
{
    return Team::create(['name' => $name, 'is_personal' => false]);
}

test('a freshly registered user cannot create tournaments on their own personal team', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    expect($personalTeam)->not->toBeNull()
        ->and($personalTeam->is_personal)->toBeTrue()
        ->and($user->ownsTeam($personalTeam))->toBeTrue();

    expect($user->can('create', [Tournament::class, $personalTeam]))->toBeFalse();
    expect($user->can('create', [Competition::class, $personalTeam]))->toBeFalse();
    expect($user->can('create', [Ranking::class, $personalTeam]))->toBeFalse();
});

test('an owner of a real federation can manage its tournaments', function () {
    $user = User::factory()->create();
    $federation = makeFederation();
    $federation->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);

    expect($user->can('create', [Tournament::class, $federation]))->toBeTrue();

    $tournament = Tournament::create(['team_id' => $federation->id, 'name' => 'Torneo Apertura']);

    expect($user->can('update', $tournament))->toBeTrue()
        ->and($user->can('process', $tournament))->toBeTrue()
        ->and($user->can('delete', $tournament))->toBeTrue();
});

test('a plain member of a federation can view but not manage its tournaments', function () {
    $user = User::factory()->create();
    $federation = makeFederation();
    $federation->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);
    $tournament = Tournament::create(['team_id' => $federation->id, 'name' => 'Torneo Apertura']);

    expect($user->can('view', $tournament))->toBeTrue()
        ->and($user->can('update', $tournament))->toBeFalse()
        ->and($user->can('delete', $tournament))->toBeFalse();
});

test('staff of one federation cannot manage another federation data', function () {
    $userA = User::factory()->create();
    $federationA = makeFederation('Federación A');
    $federationA->memberships()->create(['user_id' => $userA->id, 'role' => TeamRole::Owner]);

    $federationB = makeFederation('Federación B');
    $tournamentB = Tournament::create(['team_id' => $federationB->id, 'name' => 'Torneo de B']);

    expect($userA->can('view', $tournamentB))->toBeFalse()
        ->and($userA->can('update', $tournamentB))->toBeFalse()
        ->and($userA->can('delete', $tournamentB))->toBeFalse();
});

test('players are a shared directory manageable by staff of any real federation, not by personal-team users', function () {
    $staff = User::factory()->create();
    $federation = makeFederation();
    $federation->memberships()->create(['user_id' => $staff->id, 'role' => TeamRole::Admin]);

    $casual = User::factory()->create();

    expect($staff->can('create', Player::class))->toBeTrue()
        ->and($casual->can('create', Player::class))->toBeFalse();

    $player = Player::create(['name' => 'Leandro', 'last_name' => 'Cardoso']);

    expect($staff->can('update', $player))->toBeTrue()
        ->and($casual->can('update', $player))->toBeFalse();
});
