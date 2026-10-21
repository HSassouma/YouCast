<?php

namespace App\Models;

use App\Enums\TeamMemberRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Only the learner supplied team name may be mass-assigned. Ownership
     * (`leader_id`), the join code and the registration state are assigned
     * explicitly by server-side code.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_registered' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team): void {
            if (blank($team->join_code)) {
                $team->join_code = static::generateJoinCode();
            }
        });
    }

    public static function generateJoinCode(): string
    {
        for ($attempt = 1; ; $attempt++) {
            $code = strtoupper(Str::random(8));

            if (! static::query()->where('join_code', $code)->exists()) {
                return $code;
            }

            if ($attempt >= 5) {
                throw ValidationException::withMessages([
                    'join_code' => 'Could not allocate a unique team code. Please try again.',
                ]);
            }
        }
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    public function isFull(): bool
    {
        $max = $this->activity?->max_team_size;

        return $max !== null && $this->memberCount() >= $max;
    }

    public function meetsMinimumSize(): bool
    {
        $min = $this->activity?->min_team_size ?? 1;

        return $this->memberCount() >= $min;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isLeader(User $user): bool
    {
        return (int) $this->leader_id === (int) $user->id;
    }

    public function leaderMembership(): ?TeamMember
    {
        return $this->members()->where('role', TeamMemberRole::Leader)->first();
    }
}
