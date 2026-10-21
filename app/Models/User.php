<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Password-reset emails are intentionally disabled.
     *
     * The simplified recovery flow runs entirely in the browser. This guard
     * prevents a future call to Laravel's password broker from silently
     * reintroducing an email-based reset path.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        throw new \LogicException('Password-reset emails are disabled in this application.');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isLearner(): bool
    {
        return $this->role === UserRole::Learner;
    }

    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->where('status', RegistrationStatus::Registered);
    }

    public function challengeIdeas(): HasMany
    {
        return $this->hasMany(ChallengeIdea::class, 'author_id');
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'student_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

}
