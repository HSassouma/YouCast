<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->teamChallenge()->published(),
            'leader_id' => User::factory()->learner(),
            'name' => fake()->unique()->words(2, true).' Team',
            'join_code' => strtoupper(Str::random(8)),
            'is_registered' => false,
            'registered_at' => null,
        ];
    }
}
