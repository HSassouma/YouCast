<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $this->user()?->can('create', [Team::class, $activity]) ?? false;
    }

    public function rules(): array
    {
        $activity = $this->route('activity');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:80',
                Rule::unique('teams', 'name')->where('activity_id', $activity->id),
            ],
        ];
    }
}
