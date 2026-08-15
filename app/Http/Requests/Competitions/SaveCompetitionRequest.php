<?php

namespace App\Http\Requests\Competitions;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        if ($competition instanceof Competition) {
            return $this->user()->can('update', $competition);
        }

        return $this->user()->can('create', [Competition::class, $this->route('current_team')]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['in_progress' => $this->boolean('in_progress')]);
    }

    public function rules(): array
    {
        $competition = $this->route('competition');

        return [
            'name' => ['required', 'string', 'max:255'],
            'acronym' => ['nullable', 'string', 'max:32'],
            'url' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('competitions', 'url')->ignore($competition?->id),
            ],
            'ranking_id' => ['nullable', 'integer', 'exists:rankings,id'],
            'speed' => ['required', 'in:standard,rapid,blitz'],
            'discarded_games' => ['required', 'integer', 'min:0'],
            'in_progress' => ['boolean'],
            'begins' => ['nullable', 'date'],
            'ends' => ['nullable', 'date', 'after_or_equal:begins'],
        ];
    }
}
