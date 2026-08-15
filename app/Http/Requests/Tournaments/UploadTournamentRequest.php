<?php

namespace App\Http\Requests\Tournaments;

use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;

class UploadTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Tournament::class, $this->route('current_team')]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:trf,txt'],
            'ranking_id' => ['required', 'integer', 'exists:rankings,id'],
        ];
    }
}
