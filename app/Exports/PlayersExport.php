<?php

namespace App\Exports;

use App\Models\Player;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlayersExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly int $rankingId,
        private readonly string $speed,
    ) {}

    public function collection(): Enumerable
    {
        return Player::whereHas('rankings', fn ($query) => $query->where('ranking_id', $this->rankingId))
            ->with(['rankings' => fn ($query) => $query->where('ranking_id', $this->rankingId)])
            ->orderBy('last_name')
            ->get();
    }

    public function headings(): array
    {
        return ['ID_No', 'Name', 'Sex', 'Fed', 'ClubName', 'Birthday', 'Fide_No', 'Rtg_Int', 'Title', 'K'];
    }

    public function map($player): array
    {
        $pivot = $player->rankings->first()?->pivot;
        $eloColumn = "{$this->speed}_elo";
        $kColumn = "k_{$this->speed}_elo";

        return [
            $player->id,
            trim("{$player->last_name}, {$player->name}"),
            $player->gender === true ? 'm' : ($player->gender === false ? 'f' : ''),
            $player->country,
            $player->club_name,
            optional($player->birth_date)->format('Y-m-d'),
            $pivot?->id_on_ranking,
            $pivot?->{$eloColumn},
            $player->title,
            $pivot?->{$kColumn},
        ];
    }
}
