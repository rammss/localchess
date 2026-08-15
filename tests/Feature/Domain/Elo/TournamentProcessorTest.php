<?php

use App\Domain\Elo\PlayerMatcher;
use App\Domain\Elo\PlayerRosterResolver;
use App\Domain\Elo\TournamentProcessor;
use App\Domain\Elo\Trf16Parser;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\PlayerTournament;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\Tournament;
use Tests\Fixtures\Trf16Builder;

function seedEstablishedPlayer(Ranking $ranking, string $lastName, string $name, int $ratingId, float $elo, int $k = 20): Player
{
    $player = Player::create(['name' => $name, 'last_name' => $lastName]);

    PlayerRanking::create([
        'ranking_id' => $ranking->id,
        'player_id' => $player->id,
        'id_on_ranking' => $ratingId,
        'standard_elo' => $elo,
        'k_standard_elo' => $k,
    ]);

    return $player;
}

test('a full tournament run computes ELO variations correctly and updates every player', function () {
    $team = Team::create(['name' => 'Federación de Prueba', 'is_personal' => false]);
    $ranking = Ranking::create([
        'owner_team_id' => $team->id,
        'name' => 'Ranking de Prueba',
        'acronym' => 'RP',
        'ranking_base' => 1400,
        'ranking_restart' => 1000,
        'ranking_max_entry' => 2200,
        'valuation_games_standard' => 5,
        'k_base' => 40,
        'last_used_id' => 200,
        'k_rules' => ['rank_category' => false, 'rank_underage' => false],
    ]);

    // Ratings match the seeded players so PlayerMatcher finds them by id_on_ranking.
    seedEstablishedPlayer($ranking, 'García', 'Ana', 101, 1800);
    seedEstablishedPlayer($ranking, 'López', 'Bruno', 102, 1700);
    seedEstablishedPlayer($ranking, 'Martínez', 'Carla', 103, 1600);
    seedEstablishedPlayer($ranking, 'Núñez', 'Diego', 104, 1500);

    $path = sys_get_temp_dir().'/trf16_processor_test.trf';
    (new Trf16Builder)
        ->header(name: 'Torneo de Prueba', players: 4, playersRank: 4)
        ->rounds(['2026/01/10', '2026/01/11'])
        ->player(1, 'García', 'Ana', 1800, 101, points: 2.0, rank: 1, games: [
            ['rival' => 4, 'color' => 'w', 'result' => '1'],
            ['rival' => 2, 'color' => 'w', 'result' => '1'],
        ])
        ->player(2, 'López', 'Bruno', 1700, 102, points: 1.0, rank: 2, games: [
            ['rival' => 3, 'color' => 'w', 'result' => '1'],
            ['rival' => 1, 'color' => 'b', 'result' => '0'],
        ])
        ->player(3, 'Martínez', 'Carla', 1600, 103, points: 0.0, rank: 4, games: [
            ['rival' => 2, 'color' => 'b', 'result' => '0'],
            ['rival' => 4, 'color' => 'b', 'result' => '0'],
        ])
        ->player(4, 'Núñez', 'Diego', 1500, 104, points: 1.0, rank: 3, games: [
            ['rival' => 1, 'color' => 'b', 'result' => '0'],
            ['rival' => 3, 'color' => 'w', 'result' => '1'],
        ])
        ->writeTo($path);

    $parsed = (new Trf16Parser)->parse($path);
    unlink($path);

    $tournament = Tournament::create(['team_id' => $team->id, 'name' => $parsed->name]);

    $playersByStartingRank = (new PlayerRosterResolver(new PlayerMatcher))->resolve($parsed, $ranking);

    expect($playersByStartingRank)->toHaveCount(4);

    app(TournamentProcessor::class)->process($tournament, $parsed, $ranking, 'standard', $playersByStartingRank);

    $tournament->refresh();
    expect($tournament->processed)->toBeTrue()
        ->and($tournament->rounds_count)->toBe(2)
        ->and($tournament->amount_games)->toBe(4)
        ->and(Game::where('tournament_id', $tournament->id)->count())->toBe(4);

    // Hand-computed expectations (see the Fase 3 work notes): every game
    // uses each side's *pre-tournament* rating for the expected-score calc.
    $garcia = $playersByStartingRank[1]->fresh();
    $lopez = $playersByStartingRank[2]->fresh();
    $martinez = $playersByStartingRank[3]->fresh();
    $nunez = $playersByStartingRank[4]->fresh();

    $eloFor = fn (Player $player) => PlayerRanking::where('ranking_id', $ranking->id)->where('player_id', $player->id)->first()->standard_elo;

    expect((float) $eloFor($garcia))->toEqualWithDelta(1810.2, 0.01)
        ->and((float) $eloFor($lopez))->toEqualWithDelta(1700.0, 0.01)
        ->and((float) $eloFor($martinez))->toEqualWithDelta(1580.0, 0.01)
        ->and((float) $eloFor($nunez))->toEqualWithDelta(1509.8, 0.01);

    // PlayerTournament rows carry the pre-tournament elo_start, the file
    // rank and points straight from the parsed tournament.
    $garciaRow = PlayerTournament::where('tournament_id', $tournament->id)->where('player_id', $garcia->id)->first();
    expect((float) $garciaRow->elo_start)->toBe(1800.0)
        ->and((float) $garciaRow->points)->toBe(2.0)
        ->and($garciaRow->position)->toBe(1);

    // rank_category is off for this ranking, so no awards are computed.
    expect($tournament->awards)->toBeNull();
});

test('a brand new, unrated player is created on the fly and enters the ranking after enough games', function () {
    $team = Team::create(['name' => 'Federación Nuevos', 'is_personal' => false]);
    $ranking = Ranking::create([
        'owner_team_id' => $team->id,
        'name' => 'Ranking Nuevos',
        'acronym' => 'RN',
        'ranking_base' => 1000,
        'ranking_restart' => 800,
        'ranking_max_entry' => 2200,
        'valuation_games_standard' => 1,
        'k_base' => 40,
        'last_used_id' => 0,
        'k_rules' => ['rank_category' => false, 'rank_underage' => false],
    ]);

    $opponent = seedEstablishedPlayer($ranking, 'Rival', 'Establecido', 501, 1800);

    $path = sys_get_temp_dir().'/trf16_new_player_test.trf';
    (new Trf16Builder)
        ->header(name: 'Torneo Debut', players: 2, playersRank: 2)
        ->rounds(['2026/02/01'])
        ->player(1, 'Novato', 'Pablo', 0, 999, points: 0.5, rank: 2, games: [
            ['rival' => 2, 'color' => 'b', 'result' => '='],
        ])
        ->player(2, 'Rival', 'Establecido', 1800, 501, points: 0.5, rank: 1, games: [
            ['rival' => 1, 'color' => 'w', 'result' => '='],
        ])
        ->writeTo($path);

    $parsed = (new Trf16Parser)->parse($path);
    unlink($path);

    $tournament = Tournament::create(['team_id' => $team->id, 'name' => $parsed->name]);
    $resolver = new PlayerRosterResolver(new PlayerMatcher);
    $playersByStartingRank = $resolver->resolve($parsed, $ranking);

    $newPlayer = $playersByStartingRank[1];
    expect($newPlayer->id)->not->toBe($opponent->id)
        ->and($newPlayer->last_name)->toBe('Novato');

    $newPlayerRanking = PlayerRanking::where('ranking_id', $ranking->id)->where('player_id', $newPlayer->id)->first();
    expect($newPlayerRanking)->not->toBeNull()
        ->and($newPlayerRanking->id_on_ranking)->toBe(1);

    app(TournamentProcessor::class)->process($tournament, $parsed, $ranking, 'standard', $playersByStartingRank);

    $newPlayerRanking->refresh();
    // One draw against an 1800 rival, entering the ranking (valuation_games=1):
    // performance = scoreToDifference(1 game, 0.5 pts, 1800 rival, entering=true)
    // adds 2 virtual draws vs 1800 -> 3 games, 1.5 pts, 5400 rival sum -> 50% -> diff 0 -> 1800.
    expect((float) $newPlayerRanking->standard_elo)->toEqualWithDelta(1800.0, 0.01);
});
