<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertChallengeIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $this->user()?->can('convert', $idea) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
        ];
    }
}
