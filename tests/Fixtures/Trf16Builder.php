<?php

namespace Tests\Fixtures;

/**
 * Builds minimal, valid TRF16 fixture files for tests, using the exact same
 * column offsets Trf16Parser reads from. There are no real production
 * TRF16 files available to this new project (see the Fase 3 plan notes),
 * so this stands in as the source of truth for "what a well-formed file
 * looks like" until real ones are gathered from LocalChess 1.0's history.
 */
final class Trf16Builder
{
    /** @var list<string> */
    private array $lines = [];

    public function header(string $name = 'Torneo de Prueba', int $players = 4, int $playersRank = 4): self
    {
        $this->lines[] = $this->field('012', $name);
        $this->lines[] = $this->field('022', 'Club de Prueba');
        $this->lines[] = $this->field('032', 'ARG');
        $this->lines[] = $this->field('042', '2026/01/10');
        $this->lines[] = $this->field('052', '2026/01/11');
        $this->lines[] = $this->field('062', (string) $players);
        $this->lines[] = $this->field('072', (string) $playersRank);
        $this->lines[] = $this->field('092', 'Individual: Swiss-System');
        $this->lines[] = $this->field('102', 'Juan Perez');
        $this->lines[] = $this->field('122', '90m+30s');

        return $this;
    }

    /**
     * @param  list<string>  $dates
     */
    public function rounds(array $dates): self
    {
        $buffer = $this->blank(91);
        $this->set($buffer, 0, '132');

        foreach ($dates as $i => $date) {
            $this->set($buffer, 91 + ($i * 10), str_pad($date, 9, ' '));
        }

        $this->lines[] = $buffer;

        return $this;
    }

    /**
     * @param  list<array{rival: int, color: string, result: string}>  $games
     */
    public function player(
        int $startingRank,
        string $lastName,
        string $firstName,
        int $rating,
        int $ratingId,
        float $points,
        int $rank,
        array $games,
        string $birthDate = '1990/01/01',
        string $title = '',
        string $fideFederation = 'ARG',
    ): self {
        $buffer = $this->blank(91 + (count($games) * 10));

        $this->set($buffer, 0, '001');
        $this->set($buffer, 4, str_pad((string) $startingRank, 4, ' ', STR_PAD_LEFT));
        $this->set($buffer, 9, 'm');
        $this->set($buffer, 10, str_pad($title, 3, ' '));
        $this->set($buffer, 14, str_pad("{$lastName}, {$firstName}", 32, ' '));
        $this->set($buffer, 48, str_pad((string) $rating, 5, ' ', STR_PAD_LEFT));
        $this->set($buffer, 53, str_pad($fideFederation, 3, ' '));
        $this->set($buffer, 57, str_pad((string) $ratingId, 12, ' ', STR_PAD_LEFT));
        $this->set($buffer, 69, str_pad($birthDate, 10, ' '));
        $this->set($buffer, 80, str_pad(number_format($points, 1), 5, ' ', STR_PAD_LEFT));
        $this->set($buffer, 85, str_pad((string) $rank, 4, ' ', STR_PAD_LEFT));

        foreach ($games as $i => $game) {
            $block = str_pad((string) $game['rival'], 5, ' ', STR_PAD_LEFT)
                .' '.$game['color']
                .' '.$game['result'];
            $this->set($buffer, 91 + ($i * 10), str_pad($block, 9, ' '));
        }

        $this->lines[] = $buffer;

        return $this;
    }

    public function build(): string
    {
        return implode("\n", $this->lines)."\n";
    }

    public function writeTo(string $path): string
    {
        file_put_contents($path, $this->build());

        return $path;
    }

    private function field(string $code, string $value): string
    {
        return $code.' '.$value;
    }

    private function blank(int $length): string
    {
        return str_repeat(' ', $length);
    }

    private function set(string &$buffer, int $offset, string $value): void
    {
        $buffer = substr_replace($buffer, $value, $offset, strlen($value));
    }
}
