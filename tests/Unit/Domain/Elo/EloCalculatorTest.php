<?php

use App\Domain\Elo\EloCalculator;

beforeEach(function () {
    $this->calculator = new EloCalculator;
});

test('equal ratings expect a 50% score', function () {
    expect($this->calculator->expectedScoreForHigherRated(0))->toBe(0.50)
        ->and($this->calculator->expectedScoreForHigherRated(3))->toBe(0.50)
        ->and($this->calculator->expectedScoreForHigherRated(4))->toBe(0.51);
});

test('a 400 point difference is FIDE\'s classic ~92% expected score', function () {
    expect($this->calculator->expectedScoreForHigherRated(400))->toBe(0.92);
});

test('variation is zero-sum between two equally rated players with equal K', function () {
    $result = $this->calculator->variation(
        whiteElo: 1600, whiteK: 20, blackElo: 1600, blackK: 20,
        whiteResult: 1.0, blackResult: 0.0,
    );

    expect($result['white'])->toBe(10.0)
        ->and($result['black'])->toBe(-10.0);
});

test('the stronger player gains little for an expected win, and loses more for an upset loss', function () {
    $expectedWin = $this->calculator->variation(
        whiteElo: 1800, whiteK: 20, blackElo: 1600, blackK: 20,
        whiteResult: 1.0, blackResult: 0.0,
    );
    $upsetLoss = $this->calculator->variation(
        whiteElo: 1800, whiteK: 20, blackElo: 1600, blackK: 20,
        whiteResult: 0.0, blackResult: 1.0,
    );

    expect($expectedWin['white'])->toBeLessThan($upsetLoss['white'] * -1)
        ->and(abs($expectedWin['white']))->toBeLessThan(abs($upsetLoss['white']));
});

test('draws barely move a much stronger player\'s rating downward', function () {
    $result = $this->calculator->variation(
        whiteElo: 2000, whiteK: 20, blackElo: 1500, blackK: 20,
        whiteResult: 0.5, blackResult: 0.5,
    );

    expect($result['white'])->toBeLessThan(0)
        ->and($result['black'])->toBeGreaterThan(0);
});

test('a brand new player entering the ranking with no real games performs at the virtual opponent rating', function () {
    $performance = $this->calculator->performance(
        gamesPlayed: 0, pointsScored: 0, opponentRatingSum: 0, isEnteringRanking: true,
    );

    expect($performance)->toBe(1800.0);
});

test('performance with zero games and not entering the ranking is zero', function () {
    expect($this->calculator->performance(0, 0, 0, false))->toBe(0.0);
});

test('a player scoring 100% against 1600-rated opposition performs well above 1600', function () {
    $performance = $this->calculator->performance(
        gamesPlayed: 4, pointsScored: 4, opponentRatingSum: 4 * 1600, isEnteringRanking: false,
    );

    expect($performance)->toBeGreaterThan(1600 + 400 - 1);
});
