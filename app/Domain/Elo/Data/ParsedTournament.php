<?php

namespace App\Domain\Elo\Data;

/**
 * The full result of parsing a TRF16 file: tournament-level metadata plus
 * every declared participant, sorted by final rank.
 */
final readonly class ParsedTournament
{
    /**
     * @param  list<ParsedPlayer>  $players
     * @param  list<string>  $roundDates  one entry per round, in order
     */
    public function __construct(
        public ?string $name,
        public ?string $place,
        public ?string $country,
        public ?string $begins,
        public ?string $ends,
        public ?int $amountPlayers,
        public ?int $amountPlayersRank,
        public ?int $amountTeams,
        public ?string $type,
        public ?string $mainArbiter,
        public ?string $otherArbiters,
        public ?string $timeControl,
        public float $playerAverageRating,
        public bool $hasTeams,
        public array $players,
        public array $roundDates,
    ) {}

    public function roundsCount(): int
    {
        return count($this->roundDates);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'place' => $this->place,
            'country' => $this->country,
            'begins' => $this->begins,
            'ends' => $this->ends,
            'amountPlayers' => $this->amountPlayers,
            'amountPlayersRank' => $this->amountPlayersRank,
            'amountTeams' => $this->amountTeams,
            'type' => $this->type,
            'mainArbiter' => $this->mainArbiter,
            'otherArbiters' => $this->otherArbiters,
            'timeControl' => $this->timeControl,
            'playerAverageRating' => $this->playerAverageRating,
            'hasTeams' => $this->hasTeams,
            'players' => array_map(fn (ParsedPlayer $player) => $player->toArray(), $this->players),
            'roundDates' => $this->roundDates,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            place: $data['place'],
            country: $data['country'],
            begins: $data['begins'],
            ends: $data['ends'],
            amountPlayers: $data['amountPlayers'],
            amountPlayersRank: $data['amountPlayersRank'],
            amountTeams: $data['amountTeams'],
            type: $data['type'],
            mainArbiter: $data['mainArbiter'],
            otherArbiters: $data['otherArbiters'],
            timeControl: $data['timeControl'],
            playerAverageRating: $data['playerAverageRating'],
            hasTeams: $data['hasTeams'],
            players: array_map(fn (array $player) => ParsedPlayer::fromArray($player), $data['players']),
            roundDates: $data['roundDates'],
        );
    }
}
