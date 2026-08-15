<?php

namespace App\Imports;

use App\Models\Player;
use App\Models\PlayerRanking;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Imports a FIDE-style player list (ID_No, Name, Sex, Fed, ClubNumber,
 * ClubName, Birthday, Rtg_Nat, Fide_No, Rtg_int, Title, K columns) into the
 * shared Player directory, and enrolls each new player in the given Ranking.
 */
class PlayerImport implements ToModel, WithHeadingRow, WithValidation
{
    public function __construct(private readonly int $rankingId) {}

    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function model(array $row): ?Player
    {
        $parts = explode(',', (string) $row['name']);
        $lastName = isset($parts[0]) ? ucwords(strtolower(trim($parts[0]))) : '';
        $firstName = isset($parts[1]) ? ucwords(strtolower(trim($parts[1]))) : '';

        $player = Player::create([
            'name' => $firstName,
            'last_name' => $lastName,
            'gender' => isset($row['sex']) ? strtolower((string) $row['sex']) === 'm' : null,
            'country' => $row['fed'] ?? null,
            'club_name' => $row['clubname'] ?? null,
            'birth_date' => $this->excelDate($row['birthday'] ?? null),
            'title' => $row['title'] ?: null,
        ]);

        PlayerRanking::create([
            'ranking_id' => $this->rankingId,
            'player_id' => $player->id,
            'is_default' => true,
            'standard_elo' => $row['rtg_nat'] ?? 0,
            'rapid_elo' => $row['rtg_int'] ?? 0,
            'blitz_elo' => $row['rtg_int'] ?? 0,
            'k_standard_elo' => $row['k'] ?? 0,
            'id_on_ranking' => $row['fide_no'] ?? $row['id_no'],
        ]);

        return $player;
    }

    private function excelDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(((float) $value - 25569) * 86400)->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
