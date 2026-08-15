<?php

use App\Domain\Elo\CompetitionAggregator;
use App\Models\Competition;
use App\Models\CompetitionPlayer;
use App\Models\Player;
use App\Models\PlayerTournament;
use App\Models\Team;
use App\Models\Tournament;

function playInTournament(Tournament $tournament, Player $player, float $points, float $performance = 0, float $eloVariation = 0): void
{
    PlayerTournament::create([
        'tournament_id' => $tournament->id,
        'player_id' => $player->id,
        'points' => $points,
        'performance' => $performance,
        'elo_variation' => $eloVariation,
    ]);
}

test('standings accumulate across tournaments, discard the worst result, and an absent player keeps their own data', function () {
    $team = Team::create(['name' => 'Federación Circuito', 'is_personal' => false]);
    $competition = Competition::create([
        'team_id' => $team->id,
        'name' => 'Circuito de Prueba',
        'url' => 'circuito-de-prueba',
        'discarded_games' => 3,
    ]);

    $playerA = Player::create(['name' => 'Jugador', 'last_name' => 'A']);
    $playerB = Player::create(['name' => 'Jugador', 'last_name' => 'B']);

    $aggregator = app(CompetitionAggregator::class);

    // T1: both A and B play.
    $t1 = Tournament::create(['team_id' => $team->id, 'name' => 'Torneo 1']);
    playInTournament($t1, $playerA, points: 1.0);
    playInTournament($t1, $playerB, points: 1.5);
    $aggregator->addTournament($t1, $competition);

    // T2: both play.
    $t2 = Tournament::create(['team_id' => $team->id, 'name' => 'Torneo 2']);
    playInTournament($t2, $playerA, points: 3.0);
    playInTournament($t2, $playerB, points: 1.0);
    $aggregator->addTournament($t2, $competition);

    // T3: both play.
    $t3 = Tournament::create(['team_id' => $team->id, 'name' => 'Torneo 3']);
    playInTournament($t3, $playerA, points: 2.0);
    playInTournament($t3, $playerB, points: 0.5);
    $aggregator->addTournament($t3, $competition);

    // T4: only A plays — B is absent (this is what exercised the 1.0 stale-variable bug).
    $t4 = Tournament::create(['team_id' => $team->id, 'name' => 'Torneo 4']);
    playInTournament($t4, $playerA, points: 0.5);
    $aggregator->addTournament($t4, $competition);

    $standingA = CompetitionPlayer::where('competition_id', $competition->id)->where('player_id', $playerA->id)->first();
    $standingB = CompetitionPlayer::where('competition_id', $competition->id)->where('player_id', $playerB->id)->first();

    // A: 4 tournaments played, discarded_games=3 -> discard floor(4/3)=1 tournament,
    // no absences, so the worst played result (0.5, from T4) is dropped:
    // (1.0 + 3.0 + 2.0 + 0.5) - 0.5 = 6.0.
    expect($standingA->tournament_amount)->toBe(4)
        ->and($standingA->played_events)->toBe(4)
        ->and($standingA->discarded_tournaments)->toBe(1)
        ->and($standingA->discarded_tournament_ids)->toBe([$t4->id])
        ->and($standingA->totalPoints())->toEqualWithDelta(6.0, 0.001);

    // B: 4 tournaments elapsed, but only played 3 (absent from T4). The one
    // absence already accounts for the discard credit, so nothing extra is
    // dropped from B's own played results — and B's numbers must reflect
    // B's own tournaments, not A's (the historical bug).
    expect($standingB->tournament_amount)->toBe(4)
        ->and($standingB->played_events)->toBe(3)
        ->and($standingB->discarded_tournaments)->toBe(1)
        ->and($standingB->discarded_tournament_ids)->toBe([])
        ->and($standingB->totalPoints())->toEqualWithDelta(3.0, 0.001);

    // A leads on scoreboard (6.5 vs 3.0).
    expect($standingA->position)->toBe(1)
        ->and($standingB->position)->toBe(2);
});
