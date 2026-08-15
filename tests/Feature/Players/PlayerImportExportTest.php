<?php

use App\Enums\TeamRole;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('the import and export form pages render for staff', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Admin]);
    Ranking::create(['owner_team_id' => $team->id, 'name' => 'R', 'acronym' => 'R']);

    $this->actingAs($user)->get(route('players.import.form', ['current_team' => $team->slug]))->assertOk();
    $this->actingAs($user)->get(route('players.export.form', ['current_team' => $team->slug]))->assertOk();
});

test('exporting a ranking streams an xlsx file', function () {
    $user = User::factory()->create();
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Admin]);
    $ranking = Ranking::create(['owner_team_id' => $team->id, 'name' => 'R', 'acronym' => 'R']);
    $player = Player::create(['name' => 'Ana', 'last_name' => 'García']);
    PlayerRanking::create(['ranking_id' => $ranking->id, 'player_id' => $player->id, 'id_on_ranking' => 1, 'standard_elo' => 1500]);

    Excel::fake();

    $this->actingAs($user)
        ->get(route('players.export', ['current_team' => $team->slug, 'ranking_id' => $ranking->id, 'speed' => 'standard']))
        ->assertOk();

    Excel::assertDownloaded('jugadores.xlsx');
});
