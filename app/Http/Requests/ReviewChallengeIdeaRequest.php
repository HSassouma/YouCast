<?php

namespace App\Http\Requests;

use App\Enums\IdeaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewChallengeIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $this->user()?->can('review', $idea) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([IdeaStatus::Accepted->value, IdeaStatus::Rejected->value])],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
