<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    /**
     * Number of times a write transaction is retried when the database reports
     * a concurrency error (for example "database is locked" on SQLite).
     */
    public const TRANSACTION_ATTEMPTS = 3;

    public function registerIndividual(User $user, Activity $activity): Registration
    {
        $this->assertCanRegister($user, $activity);

        if ($activity->usesTeams()) {
            throw ValidationException::withMessages([
                'activity' => 'Cette activité nécessite une inscription en équipe.',
            ]);
        }

        return DB::transaction(function () use ($user, $activity) {
            $locked = Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();

            $this->assertCapacity($locked, 1);
            $this->assertNotAlreadyRegistered($user, $locked);

            $existing = Registration::query()
                ->where('user_id', $user->id)
                ->where('activity_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isActive()) {
                    throw ValidationException::withMessages([
                        'activity' => 'Vous êtes déjà inscrit à cette activité.',
                    ]);
                }

                $existing->team_id = null;
                $existing->status = RegistrationStatus::Registered;
                $existing->registered_at = now();
                $existing->cancelled_at = null;
                $existing->save();

                return $existing->fresh();
            }

            $registration = new Registration;
            $registration->user_id = $user->id;
            $registration->activity_id = $locked->id;
            $registration->team_id = null;
            $registration->status = RegistrationStatus::Registered;
            $registration->registered_at = now();
            $registration->save();

            return $registration;
        }, self::TRANSACTION_ATTEMPTS);
    }

    public function cancel(User $user, Activity $activity): Registration
    {
        if (! $activity->cancellation_allowed) {
            throw ValidationException::withMessages([
                'activity' => "L'annulation n'est pas autorisée pour cette activité.",
            ]);
        }

        if (! $activity->registrationWindowOpen() && ! $activity->isPublished()) {
            throw ValidationException::withMessages([
                'activity' => 'Vous ne pouvez plus annuler cette inscription.',
            ]);
        }

        $registration = Registration::query()
            ->where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->first();

        if (! $registration || ! $registration->isActive()) {
            throw ValidationException::withMessages([
                'activity' => "Vous n'avez pas d'inscription active à cette activité.",
            ]);
        }

        if ($activity->usesTeams()) {
            throw ValidationException::withMessages([
                'activity' => 'Quittez votre équipe ou demandez à son responsable de la désinscrire.',
            ]);
        }

        $registration->status = RegistrationStatus::Cancelled;
        $registration->cancelled_at = now();
        $registration->save();

        return $registration->fresh();
    }

    public function assertCanRegister(User $user, Activity $activity): void
    {
        if (! $activity->registrationWindowOpen()) {
            throw ValidationException::withMessages([
                'activity' => 'Les inscriptions ne sont pas ouvertes pour cette activité.',
            ]);
        }
    }

    public function assertCapacity(Activity $activity, int $seats): void
    {
        if (! $activity->hasCapacityFor($seats)) {
            throw ValidationException::withMessages([
                'activity' => 'Cette activité est complète.',
            ]);
        }
    }

    public function assertNotAlreadyRegistered(User $user, Activity $activity): void
    {
        $exists = Registration::query()
            ->where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->where('status', RegistrationStatus::Registered)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'activity' => 'Vous êtes déjà inscrit à cette activité.',
            ]);
        }
    }
}
