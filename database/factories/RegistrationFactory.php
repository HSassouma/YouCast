<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'activity_id' => Activity::factory()->published(),
            'team_id' => null,
            'status' => RegistrationStatus::Registered,
            'registered_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
