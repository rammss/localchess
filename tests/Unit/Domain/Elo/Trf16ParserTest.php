<?php

use App\Domain\Elo\Trf16Parser;
use Tests\Fixtures\Trf16Builder;

function buildSampleTrf16(string $path): string
{
    return (new Trf16Builder)
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
}

test('it parses tournament header fields', function () {
    $path = buildSampleTrf16(sys_get_temp_dir().'/trf16_header_test.trf');
    $tournament = (new Trf16Parser)->parse($path);

    expect($tournament->name)->toBe('Torneo de Prueba')
        ->and($tournament->place)->toBe('Club de Prueba')
        ->and($tournament->country)->toBe('ARG')
        ->and($tournament->begins)->toBe('2026-01-10')
        ->and($tournament->ends)->toBe('2026-01-11')
        ->and($tournament->amountPlayersRank)->toBe(4)
        ->and($tournament->roundsCount())->toBe(2)
        ->and($tournament->playerAverageRating)->toBe(1650.0);

    unlink($path);
});

test('it parses every player with their rating, rank and per-round games', function () {
    $path = buildSampleTrf16(sys_get_temp_dir().'/trf16_players_test.trf');
    $tournament = (new Trf16Parser)->parse($path);

    expect($tournament->players)->toHaveCount(4);

    // Sorted by final rank: García (1), López (2), Núñez (3), Martínez (4).
    $winner = $tournament->players[0];
    expect($winner->lastName)->toBe('García')
        ->and($winner->name)->toBe('Ana')
        ->and($winner->rating)->toBe(1800)
        ->and($winner->ratingId)->toBe(101)
        ->and($winner->points)->toBe(2.0)
        ->and($winner->rank)->toBe(1)
        ->and($winner->games)->toHaveCount(2);

    expect($winner->games[0]->rivalStartingRank)->toBe(4)
        ->and($winner->games[0]->color)->toBe('white')
        ->and($winner->games[0]->rawResult)->toBe('1');

    $last = $tournament->players[3];
    expect($last->lastName)->toBe('Martínez')
        ->and($last->points)->toBe(0.0)
        ->and($last->rank)->toBe(4);

    unlink($path);
});
