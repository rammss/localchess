<?php

namespace App\Domain\Elo;

/**
 * The pure math of the ELO engine — no database access, no side effects.
 * Ported from LocalChess 1.0's RankingService (calculateVariation,
 * scoreToDifference, diferenceToScore), which itself follows FIDE's rating
 * regulations (FIDE Handbook B.02, Annex 2 — the expected-score table).
 *
 * Being pure functions, these are the highest-value, easiest-to-verify part
 * of the whole engine — see EloCalculatorTest for the regression coverage
 * 1.0 never had.
 */
final class EloCalculator
{
    /**
     * FIDE's expected-score-to-rating-difference table, indexed by the
     * fractional score (0-100, i.e. percentage * 1) of the *weaker* side's
     * results against the *stronger* side. Value is the rating difference
     * that produces that expected score for the stronger player.
     *
     * @var array<int, int>
     */
    private const SCORE_TO_DIFFERENCE = [
        0 => -800, 1 => -677, 2 => -589, 3 => -538, 4 => -501, 5 => -470,
        6 => -444, 7 => -422, 8 => -401, 9 => -383, 10 => -366, 11 => -351,
        12 => -336, 13 => -322, 14 => -309, 15 => -296, 16 => -284, 17 => -273,
        18 => -262, 19 => -251, 20 => -240, 21 => -230, 22 => -220, 23 => -211,
        24 => -202, 25 => -193, 26 => -184, 27 => -175, 28 => -166, 29 => -158,
        30 => -149, 31 => -141, 32 => -133, 33 => -125, 34 => -117, 35 => -110,
        36 => -102, 37 => -95, 38 => -87, 39 => -80, 40 => -72, 41 => -65,
        42 => -57, 43 => -50, 44 => -43, 45 => -36, 46 => -29, 47 => -21,
        48 => -14, 49 => -7, 50 => 0, 51 => 7, 52 => 14, 53 => 21, 54 => 29,
        55 => 36, 56 => 43, 57 => 50, 58 => 57, 59 => 65, 60 => 72, 61 => 80,
        62 => 87, 63 => 95, 64 => 102, 65 => 110, 66 => 117, 67 => 125,
        68 => 133, 69 => 141, 70 => 149, 71 => 158, 72 => 166, 73 => 175,
        74 => 184, 75 => 193, 76 => 202, 77 => 211, 78 => 220, 79 => 230,
        80 => 240, 81 => 251, 82 => 262, 83 => 273, 84 => 284, 85 => 296,
        86 => 309, 87 => 322, 88 => 336, 89 => 351, 90 => 366, 91 => 383,
        92 => 401, 93 => 422, 94 => 444, 95 => 470, 96 => 501, 97 => 538,
        98 => 589, 99 => 677, 100 => 800,
    ];

    /**
     * The inverse table: rating-difference bands to the expected score of
     * the *higher-rated* side. 51 bands, each [min, max, probability].
     *
     * @var list<array{0: int, 1: int, 2: float}>
     */
    private const DIFFERENCE_TO_SCORE = [
        [0, 3, 0.50], [4, 10, 0.51], [11, 17, 0.52], [18, 25, 0.53], [26, 32, 0.54],
        [33, 39, 0.55], [40, 46, 0.56], [47, 53, 0.57], [54, 61, 0.58], [62, 68, 0.59],
        [69, 76, 0.60], [77, 83, 0.61], [84, 91, 0.62], [92, 98, 0.63], [99, 106, 0.64],
        [107, 113, 0.65], [114, 121, 0.66], [122, 129, 0.67], [130, 137, 0.68],
        [138, 145, 0.69], [146, 153, 0.70], [154, 162, 0.71], [163, 170, 0.72],
        [171, 179, 0.73], [180, 188, 0.74], [189, 197, 0.75], [198, 206, 0.76],
        [207, 215, 0.77], [216, 225, 0.78], [226, 235, 0.79], [236, 245, 0.80],
        [246, 256, 0.81], [257, 267, 0.82], [268, 278, 0.83], [279, 290, 0.84],
        [291, 302, 0.85], [303, 315, 0.86], [316, 328, 0.87], [329, 344, 0.88],
        [345, 357, 0.89], [358, 374, 0.90], [375, 391, 0.91], [392, 411, 0.92],
        [412, 432, 0.93], [433, 456, 0.94], [457, 484, 0.95], [485, 517, 0.96],
        [518, 559, 0.97], [560, 619, 0.98], [620, 735, 0.99], [736, 3000, 1.00],
    ];

    /**
     * The expected score of the higher-rated side, given the (unsigned)
     * rating difference between the two players. Null if somehow out of
     * every band (should not happen — the table covers 0..3000).
     */
    public function expectedScoreForHigherRated(int $ratingDifference): ?float
    {
        foreach (self::DIFFERENCE_TO_SCORE as [$min, $max, $probability]) {
            if ($ratingDifference >= $min && $ratingDifference <= $max) {
                return $probability;
            }
        }

        return null;
    }

    /**
     * How much each side's rating moves after one game.
     *
     * @return array{white: float, black: float}
     */
    public function variation(
        int $whiteElo,
        int $whiteK,
        int $blackElo,
        int $blackK,
        float $whiteResult,
        float $blackResult,
    ): array {
        $difference = abs($whiteElo - $blackElo);
        $expectedForHigher = $this->expectedScoreForHigherRated($difference);

        if ($expectedForHigher === null) {
            $expectedForHigher = 0.5;
            $expectedForLower = 0.5;
        } else {
            $expectedForLower = 1 - $expectedForHigher;
            // FIDE caps the effective probability at a 400-point difference.
            $expectedForLower = max($expectedForLower, 0.08);
            $expectedForHigher = min($expectedForHigher, 0.92);
        }

        if ($whiteElo > $blackElo) {
            $whiteVariation = $whiteK * ($whiteResult - $expectedForHigher);
            $blackVariation = $blackK * ($blackResult - $expectedForLower);
        } else {
            $whiteVariation = $whiteK * ($whiteResult - $expectedForLower);
            $blackVariation = $blackK * ($blackResult - $expectedForHigher);
        }

        return ['white' => $whiteVariation, 'black' => $blackVariation];
    }

    /**
     * A player's tournament performance rating from their games: the rating
     * difference (added to the average opponent rating) that would have
     * produced their actual score over that many games.
     *
     * When $isEnteringRanking is true, two virtual draws against a 1800
     * opponent are added first — FIDE's rule for a player's very first
     * rating calculation.
     */
    public function performance(
        int $gamesPlayed,
        float $pointsScored,
        float $opponentRatingSum,
        bool $isEnteringRanking,
    ): float {
        if ($isEnteringRanking) {
            $pointsScored += 1;
            $gamesPlayed += 2;
            $opponentRatingSum += 3600;
        }

        if ($gamesPlayed <= 0) {
            return 0.0;
        }

        $fractionalScore = round($pointsScored / $gamesPlayed, 2) * 100;
        $averageOpponentRating = $opponentRatingSum / $gamesPlayed;
        $difference = self::SCORE_TO_DIFFERENCE[(int) $fractionalScore] ?? 0;

        return round($difference + $averageOpponentRating, 2);
    }
}
