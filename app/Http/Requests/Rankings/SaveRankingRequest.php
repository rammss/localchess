<?php

namespace App\Http\Requests\Rankings;

use App\Http\Requests\Concerns\NormalizesDecimalInput;
use App\Models\Ranking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRankingRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $ranking = $this->route('ranking');

        if ($ranking instanceof Ranking) {
            return $this->user()->can('update', $ranking);
        }

        return $this->user()->can('create', [Ranking::class, $this->route('current_team')]);
    }

    /**
     * A native, unvalued checkbox submits "on" when checked and is simply
     * absent from the payload when unchecked — neither of which the
     * `boolean` validation rule accepts on its own. Normalize both before
     * validating instead of relying on the rule to understand HTML forms.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'state' => $this->boolean('state'),
        ]);

        $this->normalizeDecimals(['k_blitz', 'k_rapid']);
    }

    public function rules(): array
    {
        $ranking = $this->route('ranking');
        $team = $this->route('current_team');
        $teamId = is_object($team) ? $team->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'acronym' => [
                'required', 'string', 'max:32',
                Rule::unique('rankings', 'acronym')
                    ->where('owner_team_id', $teamId)
                    ->ignore($ranking?->id),
            ],
            'is_default' => ['boolean'],
            'state' => ['boolean'],
            'ranking_base' => ['required', 'integer', 'min:0'],
            'ranking_restart' => ['required', 'integer', 'min:0'],
            'ranking_max_entry' => ['required', 'integer', 'min:0'],
            'k_base' => ['required', 'integer', 'min:1'],
            'k_blitz' => ['required', 'numeric', 'min:0'],
            'k_rapid' => ['required', 'numeric', 'min:0'],
            'valuation_games_standard' => ['required', 'integer', 'min:1'],
            'valuation_games_rapid' => ['required', 'integer', 'min:1'],
            'valuation_games_blitz' => ['required', 'integer', 'min:1'],
        ];
    }
}
