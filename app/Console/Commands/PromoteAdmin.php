<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdmin extends Command
{
    protected $signature = 'youcast:promote-admin {email : The email of the user to promote}';

    protected $description = 'Grant the BDE administrator role to an existing user';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->info($user->email.' is already an administrator.');

            return self::SUCCESS;
        }

        $user->role = UserRole::Admin;
        $user->save();

        AuditLog::record(null, 'user.promoted_admin', $user);

        $this->info($user->name.' ('.$user->email.') is now a BDE administrator.');

        return self::SUCCESS;
    }
}
