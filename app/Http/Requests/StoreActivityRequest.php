<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Activity::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:8000'],
            'category' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_deadline' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'min_team_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'max_team_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cancellation_allowed' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');

            if ($type === ActivityType::TeamChallenge->value) {
                if ($this->filled('capacity') && $this->filled('min_team_size')
                    && (int) $this->input('capacity') < (int) $this->input('min_team_size')) {
                    $validator->errors()->add('capacity', 'La capacité doit permettre au moins une équipe complète.');
                }

                if (! $this->filled('min_team_size') || ! $this->filled('max_team_size')) {
                    $validator->errors()->add('min_team_size', 'Les défis en équipe exigent une taille minimale et une taille maximale.');
                }

                if ($this->filled('min_team_size') && $this->filled('max_team_size')
                    && (int) $this->input('min_team_size') > (int) $this->input('max_team_size')) {
                    $validator->errors()->add('max_team_size', 'La taille maximale doit être supérieure ou égale à la taille minimale.');
                }
            }

            // The deadline may be on or after the activity's start day. Compare
            // calendar dates so a later time on the same day remains valid.
            if ($this->filled('starts_at') && $this->filled('registration_deadline')) {
                $startsAt = Carbon::parse($this->input('starts_at'));
                $deadline = Carbon::parse($this->input('registration_deadline'));

                if ($deadline->toDateString() < $startsAt->toDateString()) {
                    $validator->errors()->add('registration_deadline', "La date limite d'inscription ne peut pas être antérieure au jour du début de l'activité.");
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['category', 'location', 'starts_at', 'ends_at', 'registration_opens_at', 'registration_deadline', 'capacity', 'min_team_size', 'max_team_size'];
        $merge = [
            'cancellation_allowed' => $this->boolean('cancellation_allowed'),
        ];

        foreach ($nullable as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
