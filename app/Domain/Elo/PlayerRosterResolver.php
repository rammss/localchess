<?php

namespace App\Domain\Elo;

use App\Domain\Elo\Data\ParsedTournament;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\Ranking;
use Carbon\Carbon;

/**
 * Resolves every parsed TRF16 participant to a real Player record — either
 * the one an admin confirmed matches (via PlayerMatcher), or a brand new
 * Player + PlayerRanking created on the spot with the next free carnet
 * number for that ranking. Ported from 1.0's UtilityService::updateOrCreatePlayer().
 */
final class PlayerRosterResolver
{
    public function __construct(private readonly PlayerMatcher $matcher) {}

    /**
     * @param  list<int>  $startingRanksToSync  parsed players whose name/birth date the admin confirmed should overwrite the existing record
     * @return array<int, Player> keyed by starting rank
     */
    public function resolve(ParsedTournament $parsed, Ranking $ranking, array $startingRanksToSync = []): array
    {
        $resolved = [];

        foreach ($parsed->players as $parsedPlayer) {
            $match = $this->matcher->match($parsedPlayer, $ranking->id);

            if ($match->isMatched()) {
                $player = $match->player;

                if (in_array($parsedPlayer->startingRank, $startingRanksToSync, true)) {
                    $player->fill([
                        'name' => $parsedPlayer->name !== '' ? $parsedPlayer->name : $player->name,
                        'last_name' => $parsedPlayer->lastName !== '' ? $parsedPlayer->lastName : $player->last_name,
                        'birth_date' => $this->parseBirthDate($parsedPlayer->birthDate) ?? $player->birth_date,
                    ])->save();
                }
            } else {
                $player = Player::create([
                    'name' => $parsedPlayer->name,
                    'last_name' => $parsedPlayer->lastName,
                    'country' => $parsedPlayer->fideFederation,
                    'birth_date' => $this->parseBirthDate($parsedPlayer->birthDate),
                ]);

                $nextId = $ranking->last_used_id + 1;

                PlayerRanking::create([
                    'ranking_id' => $ranking->id,
                    'player_id' => $player->id,
                    'id_on_ranking' => $nextId,
                ]);

                $ranking->update(['last_used_id' => $nextId]);
            }

            $resolved[$parsedPlayer->startingRank] = $player;
        }

        return $resolved;
    }

    private function parseBirthDate(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y/m/d', $raw)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
