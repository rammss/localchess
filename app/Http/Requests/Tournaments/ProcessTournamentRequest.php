<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('process', $this->route('tournament'));
    }

    public function rules(): array
    {
        return [
            'speed' => ['required', 'in:standard,rapid,blitz'],
            'competition_id' => ['nullable', 'integer', 'exists:competitions,id'],
            'sync_starting_ranks' => ['array'],
            'sync_starting_ranks.*' => ['integer'],
        ];
    }
}
