<?php

namespace App\Domain\Elo\Data;

/**
 * One "001" line of a TRF16 file: a tournament participant, as declared by
 * the pairing software, plus their per-round results (still keyed by
 * starting rank — not yet resolved to real Player records).
 */
final readonly class ParsedPlayer
{
    /**
     * @param  list<ParsedGame>  $games
     */
    public function __construct(
        public int $startingRank,
        public ?string $gender,
        public ?string $title,
        public string $name,
        public string $lastName,
        public int $rating,
        public ?string $fideFederation,
        public int $ratingId,
        public ?string $birthDate,
        public float $points,
        public int $rank,
        public array $games,
    ) {}

    public function toArray(): array
    {
        return [
            'startingRank' => $this->startingRank,
            'gender' => $this->gender,
            'title' => $this->title,
            'name' => $this->name,
            'lastName' => $this->lastName,
            'rating' => $this->rating,
            'fideFederation' => $this->fideFederation,
            'ratingId' => $this->ratingId,
            'birthDate' => $this->birthDate,
            'points' => $this->points,
            'rank' => $this->rank,
            'games' => array_map(fn (ParsedGame $game) => $game->toArray(), $this->games),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            startingRank: $data['startingRank'],
            gender: $data['gender'],
            title: $data['title'],
            name: $data['name'],
            lastName: $data['lastName'],
            rating: $data['rating'],
            fideFederation: $data['fideFederation'],
            ratingId: $data['ratingId'],
            birthDate: $data['birthDate'],
            points: $data['points'],
            rank: $data['rank'],
            games: array_map(fn (array $game) => ParsedGame::fromArray($game), $data['games']),
        );
    }
}
