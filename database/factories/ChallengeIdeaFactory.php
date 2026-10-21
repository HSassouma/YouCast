<?php

namespace Database\Factories;

use App\Enums\IdeaStatus;
use App\Models\ChallengeIdea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChallengeIdea>
 */
class ChallengeIdeaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => User::factory()->learner(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(['Web', 'Mobile', 'AI', 'Design', 'DevOps']),
            'status' => IdeaStatus::Pending,
            'reviewer_id' => null,
            'review_notes' => null,
            'reviewed_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IdeaStatus::Accepted,
            'reviewer_id' => User::factory()->admin(),
            'review_notes' => 'Great idea.',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IdeaStatus::Rejected,
            'reviewer_id' => User::factory()->admin(),
            'review_notes' => 'Out of scope.',
            'reviewed_at' => now(),
        ]);
    }
}
