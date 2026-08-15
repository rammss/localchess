<?php

namespace App\Http\Requests\Players;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;

class SavePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');

        if ($player instanceof Player) {
            return $this->user()->can('update', $player);
        }

        return $this->user()->can('create', Player::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'boolean'],
            'title' => ['nullable', 'in:CM,FM,IM,GM,WCM,WFM,WIM,WGM'],
            'country' => ['nullable', 'string', 'max:255'],
            'club_team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'club_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'details' => ['nullable', 'string'],
        ];
    }
}
