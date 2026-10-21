<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Enums\TeamMemberRole;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function __construct(private readonly RegistrationService $registrations) {}

    public function createTeam(User $user, Activity $activity, string $name): Team
    {
        $this->assertTeamActivity($activity);
        $this->registrations->assertCanRegister($user, $activity);
        $this->assertUserFreeForActivity($user, $activity);

        return DB::transaction(function () use ($user, $activity, $name) {
            $locked = Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();

            $team = new Team;
            $team->activity_id = $locked->id;
            $team->leader_id = $user->id;
            $team->name = $name;
            $team->save();

            $membership = new TeamMember;
            $membership->team_id = $team->id;
            $membership->user_id = $user->id;
            $membership->activity_id = $locked->id;
            $membership->role = TeamMemberRole::Leader;
            $membership->save();

            return $team->fresh(['members']);
        }, RegistrationService::TRANSACTION_ATTEMPTS);
    }

    public function joinTeam(User $user, Activity $activity, string $joinCode): Team
    {
        $this->assertTeamActivity($activity);
        $this->registrations->assertCanRegister($user, $activity);
        $this->assertUserFreeForActivity($user, $activity);

        return DB::transaction(function () use ($user, $activity, $joinCode) {
            $team = Team::query()
                ->where('activity_id', $activity->id)
                ->where('join_code', strtoupper(trim($joinCode)))
                ->lockForUpdate()
                ->first();

            if (! $team) {
                throw ValidationException::withMessages([
                    'join_code' => 'Aucune équipe ne correspond à ce code pour cette activité.',
                ]);
            }

            if ($team->is_registered) {
                throw ValidationException::withMessages([
                    'join_code' => "Cette équipe est déjà inscrite et n'accepte plus de nouveaux membres.",
                ]);
            }

            $team->load('activity');

            if ($team->isFull()) {
                throw ValidationException::withMessages([
                    'join_code' => 'Cette équipe est déjà complète.',
                ]);
            }

            $membership = new TeamMember;
            $membership->team_id = $team->id;
            $membership->user_id = $user->id;
            $membership->activity_id = $activity->id;
            $membership->role = TeamMemberRole::Member;
            $membership->save();

            return $team->fresh(['members']);
        }, RegistrationService::TRANSACTION_ATTEMPTS);
    }

    public function leaveTeam(User $user, Team $team): void
    {
        $activity = $team->activity;

        if ($team->isLeader($user)) {
            throw ValidationException::withMessages([
                'team' => "Le responsable ne peut pas quitter l'équipe. Le transfert n'est pas disponible : dissolvez plutôt l'équipe.",
            ]);
        }

        if ($team->is_registered) {
            throw ValidationException::withMessages([
                'team' => 'Vous ne pouvez pas quitter une équipe déjà inscrite.',
            ]);
        }

        if (! $activity->registrationWindowOpen()) {
            throw ValidationException::withMessages([
                'team' => 'Vous ne pouvez plus quitter cette équipe.',
            ]);
        }

        $membership = $team->members()->where('user_id', $user->id)->first();

        if (! $membership) {
            throw ValidationException::withMessages([
                'team' => 'Vous ne faites pas partie de cette équipe.',
            ]);
        }

        $membership->delete();
    }

    public function disbandTeam(User $user, Team $team): void
    {
        if (! $team->isLeader($user)) {
            throw ValidationException::withMessages([
                'team' => "Seul le responsable de l'équipe peut la dissoudre.",
            ]);
        }

        if ($team->is_registered) {
            throw ValidationException::withMessages([
                'team' => "Une équipe inscrite ne peut pas être dissoute. Désinscrivez-la d'abord.",
            ]);
        }

        DB::transaction(function () use ($team) {
            $team->members()->delete();
            $team->delete();
        }, RegistrationService::TRANSACTION_ATTEMPTS);
    }

    public function registerTeam(User $user, Team $team): Team
    {
        $activity = $team->activity()->firstOrFail();

        if (! $team->isLeader($user)) {
            throw ValidationException::withMessages([
                'team' => "Seul le responsable de l'équipe peut l'inscrire.",
            ]);
        }

        $this->registrations->assertCanRegister($user, $activity);

        if ($team->is_registered) {
            throw ValidationException::withMessages([
                'team' => 'Cette équipe est déjà inscrite.',
            ]);
        }

        if (! $team->meetsMinimumSize()) {
            throw ValidationException::withMessages([
                'team' => "Votre équipe n'atteint pas encore la taille minimale.",
            ]);
        }

        return DB::transaction(function () use ($team, $activity) {
            $lockedActivity = Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $lockedTeam = Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
            $members = $lockedTeam->members()->lockForUpdate()->get();

            $this->registrations->assertCapacity($lockedActivity, $members->count());

            foreach ($members as $member) {
                $existing = Registration::query()
                    ->where('user_id', $member->user_id)
                    ->where('activity_id', $lockedActivity->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->isActive()) {
                    throw ValidationException::withMessages([
                        'team' => 'Un membre de l\'équipe est déjà inscrit à cette activité.',
                    ]);
                }

                if ($existing) {
                    $existing->team_id = $lockedTeam->id;
                    $existing->status = RegistrationStatus::Registered;
                    $existing->registered_at = now();
                    $existing->cancelled_at = null;
                    $existing->save();
                } else {
                    $registration = new Registration;
                    $registration->user_id = $member->user_id;
                    $registration->activity_id = $lockedActivity->id;
                    $registration->team_id = $lockedTeam->id;
                    $registration->status = RegistrationStatus::Registered;
                    $registration->registered_at = now();
                    $registration->save();
                }
            }

            $lockedTeam->is_registered = true;
            $lockedTeam->registered_at = now();
            $lockedTeam->save();

            return $lockedTeam->fresh(['members', 'activity']);
        }, RegistrationService::TRANSACTION_ATTEMPTS);
    }

    public function unregisterTeam(User $user, Team $team): Team
    {
        $activity = $team->activity()->firstOrFail();

        if (! $team->isLeader($user)) {
            throw ValidationException::withMessages([
                'team' => "Seul le responsable de l'équipe peut désinscrire l'équipe.",
            ]);
        }

        if (! $activity->cancellation_allowed) {
            throw ValidationException::withMessages([
                'team' => "L'annulation n'est pas autorisée pour cette activité.",
            ]);
        }

        if (! $activity->isPublished()) {
            throw ValidationException::withMessages([
                'team' => 'Vous ne pouvez plus désinscrire cette équipe.',
            ]);
        }

        if (! $team->is_registered) {
            throw ValidationException::withMessages([
                'team' => "Cette équipe n'est pas inscrite.",
            ]);
        }

        return DB::transaction(function () use ($team) {
            $lockedTeam = Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            Registration::query()
                ->where('team_id', $lockedTeam->id)
                ->where('status', RegistrationStatus::Registered)
                ->update([
                    'status' => RegistrationStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);

            $lockedTeam->is_registered = false;
            $lockedTeam->registered_at = null;
            $lockedTeam->save();

            return $lockedTeam->fresh();
        }, RegistrationService::TRANSACTION_ATTEMPTS);
    }

    private function assertTeamActivity(Activity $activity): void
    {
        if (! $activity->usesTeams()) {
            throw ValidationException::withMessages([
                'activity' => 'Cette activité ne se fait pas en équipe.',
            ]);
        }
    }

    private function assertUserFreeForActivity(User $user, Activity $activity): void
    {
        $this->registrations->assertNotAlreadyRegistered($user, $activity);

        $alreadyInTeam = TeamMember::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyInTeam) {
            throw ValidationException::withMessages([
                'activity' => 'Vous appartient déjà à une équipe pour cette activité.',
            ]);
        }
    }
}
