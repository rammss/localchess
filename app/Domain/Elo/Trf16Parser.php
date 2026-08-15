<?php

namespace App\Domain\Elo;

use App\Domain\Elo\Data\ParsedGame;
use App\Domain\Elo\Data\ParsedPlayer;
use App\Domain\Elo\Data\ParsedTournament;
use Carbon\Carbon;

/**
 * Parses a TRF16 file (the fixed-column tournament report format used by
 * Swiss Manager, Chess Results, and most FIDE-compatible pairing software)
 * into a structured ParsedTournament. Ported from LocalChess 1.0's
 * UtilityService::parseTournament(), with the column offsets tightened up
 * to match the TRF16 spec exactly rather than 1.0's "read to end of line
 * and let intval() stop at the first non-digit" shortcut for the rank field.
 */
final class Trf16Parser
{
    public function parse(string $path): ParsedTournament
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        $name = null;
        $place = null;
        $country = null;
        $begins = null;
        $ends = null;
        $amountPlayers = null;
        $amountPlayersRank = null;
        $amountTeams = 0;
        $type = null;
        $mainArbiter = null;
        $otherArbiters = null;
        $timeControl = null;
        $roundDates = [];
        $players = [];
        $ratingSum = 0;

        foreach ($lines as $line) {
            $code = trim(substr($line, 0, 3));

            match ($code) {
                '001' => $players[] = $this->parsePlayerLine($line, $ratingSum),
                '013' => $amountTeams++,
                '012' => $name = $this->extract($line),
                '022' => $place = $this->extract($line),
                '032' => $country = $this->extract($line),
                '042' => $begins = $this->extractDate($line),
                '052' => $ends = $this->extractDate($line),
                '062' => $amountPlayers = (int) $this->extract($line),
                '072' => $amountPlayersRank = (int) $this->extract($line),
                '092' => $type = $this->extract($line),
                '102' => $mainArbiter = $this->extract($line),
                '112' => $otherArbiters = $this->extract($line),
                '122' => $timeControl = $this->extract($line),
                '132' => $roundDates = $this->parseRoundDates($line),
                default => null,
            };
        }

        // Players are parsed in file order (before we know the round
        // count), so their per-round games get attached at read time. Do a
        // second pass to trim each player's games down to the actual
        // number of rounds now that we know it, and average the field.
        $players = array_map(
            fn (ParsedPlayer $player) => new ParsedPlayer(
                startingRank: $player->startingRank,
                gender: $player->gender,
                title: $player->title,
                name: $player->name,
                lastName: $player->lastName,
                rating: $player->rating,
                fideFederation: $player->fideFederation,
                ratingId: $player->ratingId,
                birthDate: $player->birthDate,
                points: $player->points,
                rank: $player->rank,
                games: array_slice($player->games, 0, count($roundDates)),
            ),
            $players,
        );

        usort($players, fn (ParsedPlayer $a, ParsedPlayer $b) => $a->rank <=> $b->rank);

        $playerAverage = ($amountPlayersRank && $amountPlayersRank > 0)
            ? $ratingSum / $amountPlayersRank
            : 0.0;

        return new ParsedTournament(
            name: $name,
            place: $place,
            country: $country,
            begins: $begins,
            ends: $ends,
            amountPlayers: $amountPlayers ?? count($players),
            amountPlayersRank: $amountPlayersRank,
            amountTeams: $amountTeams,
            type: $type,
            mainArbiter: $mainArbiter,
            otherArbiters: $otherArbiters,
            timeControl: $timeControl,
            playerAverageRating: round($playerAverage, 2),
            hasTeams: $amountTeams > 0,
            players: $players,
            roundDates: $roundDates,
        );
    }

    private function extract(string $line): ?string
    {
        $value = trim(substr($line, 4));

        return $value === '' ? null : $value;
    }

    private function extractDate(string $line): ?string
    {
        $raw = $this->extract($line);

        if ($raw === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y/m/d', $raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function parseRoundDates(string $line): array
    {
        $rounds = substr($line, 91);
        $dates = [];

        for ($i = 0; $i < strlen($rounds); $i += 10) {
            $round = trim(substr($rounds, $i, 9));

            if ($round === '') {
                break;
            }

            $dates[] = $round;
        }

        return $dates;
    }

    private function parsePlayerLine(string $line, int &$ratingSum): ParsedPlayer
    {
        $startingRank = (int) trim(substr($line, 4, 4));
        $gender = trim(substr($line, 9, 1)) ?: null;
        $title = trim(substr($line, 10, 3)) ?: null;
        $rawName = trim(substr($line, 14, 32));
        $rating = (int) trim(substr($line, 48, 5));
        $fideFederation = trim(substr($line, 53, 3)) ?: null;
        $ratingId = (int) trim(substr($line, 57, 12));
        $birthDate = trim(substr($line, 69, 10)) ?: null;
        $points = (float) trim(substr($line, 80, 5));
        $rank = (int) trim(substr($line, 85, 4));

        $parts = explode(',', $rawName);
        $lastName = trim($parts[0] ?? '');
        $name = trim($parts[1] ?? '');

        $games = [];
        $offset = 91;

        // We don't know the final round count on the first pass over the
        // file (the "132" header line may come after player lines in some
        // exports), so read every 10-byte block present and let the caller
        // trim to the real round count once it's known.
        while (($block = substr($line, $offset, 9)) !== '' && trim($block) !== '') {
            $rivalStartingRank = (int) trim(substr($block, 0, 5));
            $colorCode = trim(substr($block, 5, 2));
            $color = match ($colorCode) {
                'w' => 'white',
                'b' => 'black',
                default => null,
            };
            $result = trim(substr($block, 7));

            $games[] = new ParsedGame($rivalStartingRank, $color, $result);
            $offset += 10;
        }

        $ratingSum += $rating;

        return new ParsedPlayer(
            startingRank: $startingRank,
            gender: $gender,
            title: $title,
            name: $name,
            lastName: $lastName,
            rating: $rating,
            fideFederation: $fideFederation,
            ratingId: $ratingId,
            birthDate: $birthDate,
            points: $points,
            rank: $rank,
            games: $games,
        );
    }
}
