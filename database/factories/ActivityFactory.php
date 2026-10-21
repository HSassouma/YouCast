<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'created_by' => User::factory()->admin(),
            'type' => ActivityType::Event,
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(['Hackathon', 'Workshop', 'Competition', 'Social', 'Talk']),
            'location' => fake()->randomElement(['YouCode Campus', 'Online', 'Lab A', 'Main Hall']),
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(12),
            'registration_opens_at' => now()->subDay(),
            'registration_deadline' => now()->addDays(7),
            'capacity' => 30,
            'status' => ActivityStatus::Draft,
            'min_team_size' => null,
            'max_team_size' => null,
            'cancellation_allowed' => true,
            'published_at' => null,
            'closed_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivityStatus::Published,
            'published_at' => now()->subHour(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivityStatus::Closed,
            'published_at' => now()->subDays(5),
            'closed_at' => now()->subHour(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivityStatus::Archived,
            'published_at' => now()->subMonths(2),
            'closed_at' => now()->subMonth(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivityStatus::Cancelled,
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::IndividualChallenge,
            'min_team_size' => null,
            'max_team_size' => null,
        ]);
    }

    public function teamChallenge(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::TeamChallenge,
            'min_team_size' => 2,
            'max_team_size' => 4,
        ]);
    }

    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::Event,
        ]);
    }
}
