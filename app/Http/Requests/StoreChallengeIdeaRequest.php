<?php

namespace App\Http\Requests;

use App\Models\ChallengeIdea;
use Illuminate\Foundation\Http\FormRequest;

class StoreChallengeIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChallengeIdea::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:4000'],
            'category' => ['nullable', 'string', 'max:80'],
        ];
    }
}
