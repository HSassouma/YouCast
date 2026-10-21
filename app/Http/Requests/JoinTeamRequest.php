<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class JoinTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $this->user()?->can('join', [Team::class, $activity]) ?? false;
    }

    public function rules(): array
    {
        return [
            'join_code' => ['required', 'string', 'size:8'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('join_code')) {
            $this->merge([
                'join_code' => strtoupper(trim((string) $this->input('join_code'))),
            ]);
        }
    }
}
