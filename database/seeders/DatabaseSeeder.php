<?php

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\IdeaStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@youcode.local'],
            [
                'name' => 'BDE Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Admin,
            ]
        );

        if (! $admin->isAdmin()) {
            $admin->role = UserRole::Admin;
            $admin->save();
        }

        $learner = User::query()->firstOrCreate(
            ['email' => 'learner@youcode.local'],
            [
                'name' => 'YouCode Learner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Learner,
            ]
        );

        User::factory()->learner()->count(8)->create();

        Activity::factory()->event()->published()->create([
            'created_by' => $admin->id,
            'title' => 'YouCode Welcome Night',
            'description' => 'Meet the BDE, other learners, and staff. Food, music, and a short intro to the year ahead.',
            'category' => 'Social',
            'location' => 'Main Hall',
            'capacity' => 80,
            'starts_at' => now()->addDays(5)->setTime(18, 0),
            'ends_at' => now()->addDays(5)->setTime(22, 0),
            'registration_deadline' => now()->addDays(4),
        ]);

        Activity::factory()->individual()->published()->create([
            'created_by' => $admin->id,
            'title' => 'Solo Algorithm Sprint',
            'description' => 'A timed individual challenge covering data structures, clean code, and problem solving.',
            'category' => 'Competition',
            'location' => 'Lab A',
            'capacity' => 24,
            'starts_at' => now()->addDays(12)->setTime(9, 0),
            'ends_at' => now()->addDays(12)->setTime(17, 0),
            'registration_deadline' => now()->addDays(10),
        ]);

        Activity::factory()->teamChallenge()->published()->create([
            'created_by' => $admin->id,
            'title' => '48h Product Hackathon',
            'description' => 'Form a team of 2 to 4 and ship a working prototype around a surprise theme.',
            'category' => 'Hackathon',
            'location' => 'YouCode Campus',
            'capacity' => 40,
            'min_team_size' => 2,
            'max_team_size' => 4,
            'starts_at' => now()->addDays(20)->setTime(9, 0),
            'ends_at' => now()->addDays(22)->setTime(18, 0),
            'registration_deadline' => now()->addDays(18),
        ]);

        Activity::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Career Workshop Draft',
            'description' => 'CV clinic and mock interviews with alumni. Still being scheduled.',
            'category' => 'Workshop',
            'status' => ActivityStatus::Draft,
            'type' => ActivityType::Event,
        ]);

        ChallengeIdea::factory()->create([
            'author_id' => $learner->id,
            'title' => 'Green campus tracker',
            'description' => 'A challenge where teams build a tool that tracks campus energy use and suggests savings.',
            'category' => 'Web',
            'status' => IdeaStatus::Pending,
        ]);
    }
}
