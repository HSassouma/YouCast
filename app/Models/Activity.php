<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\RegistrationStatus;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Only learner-facing, non-privileged attributes may be mass-assigned.
     *
     * Ownership (`created_by`, `source_idea_id`) and workflow state
     * (`status`, `published_at`, `closed_at`) are deliberately excluded: they
     * are always assigned explicitly by server-side code so that no request
     * payload can ever reach them.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'slug',
        'description',
        'category',
        'location',
        'starts_at',
        'ends_at',
        'registration_opens_at',
        'registration_deadline',
        'capacity',
        'min_team_size',
        'max_team_size',
        'cancellation_allowed',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'status' => ActivityStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_deadline' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancellation_allowed' => 'boolean',
            'capacity' => 'integer',
            'min_team_size' => 'integer',
            'max_team_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            if (blank($activity->slug)) {
                $activity->slug = static::uniqueSlug($activity->title);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'activity';
        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * Create an activity as a draft owned by the given administrator.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createDraft(array $attributes, User $creator, ?ChallengeIdea $sourceIdea = null): static
    {
        $activity = new static;
        $activity->fill($attributes);

        // Ownership and workflow state are never mass-assigned.
        $activity->created_by = $creator->id;
        $activity->status = ActivityStatus::Draft;
        $activity->source_idea_id = $sourceIdea?->id;
        $activity->slug = static::uniqueSlug((string) $activity->title);
        $activity->saveWithUniqueSlug();

        return $activity;
    }

    /**
     * Persist the activity, regenerating the slug when a concurrent save
     * claimed it first.
     *
     * The unique index on `slug` is the real guard; the loop only recovers from
     * the rare race where two administrators save the same title at once.
     */
    public function saveWithUniqueSlug(): bool
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->save();
            } catch (QueryException $exception) {
                if ($attempt >= 5 || ! static::causedByUniqueConstraint($exception)) {
                    throw $exception;
                }

                $this->slug = static::uniqueSlug((string) $this->title, $this->getKey());
            }
        }
    }

    /**
     * Determine whether the given exception is a unique-constraint violation.
     */
    protected static function causedByUniqueConstraint(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return $exception->getCode() === '23000'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceIdea(): BelongsTo
    {
        return $this->belongsTo(ChallengeIdea::class, 'source_idea_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->where('status', RegistrationStatus::Registered);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ActivityStatus::Published->value,
            ActivityStatus::Closed->value,
        ])->notFinished();
    }

    /** Keep ended activities in storage while excluding them from learner-facing queries. */
    public function scopeNotFinished(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('ends_at')->orWhere('ends_at', '>', now());
        });
    }

    public function usesTeams(): bool
    {
        return $this->type === ActivityType::TeamChallenge;
    }

    public function isPublished(): bool
    {
        return $this->status === ActivityStatus::Published;
    }

    public function isFinished(): bool
    {
        return $this->ends_at !== null && $this->ends_at->lte(now());
    }

    public function isClosed(): bool
    {
        return $this->status === ActivityStatus::Closed;
    }

    public function isDraft(): bool
    {
        return $this->status === ActivityStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === ActivityStatus::Archived;
    }

    public function isCancelled(): bool
    {
        return $this->status === ActivityStatus::Cancelled;
    }

    public function registrationWindowOpen(): bool
    {
        if (! $this->isPublished() || $this->isFinished()) {
            return false;
        }

        $now = now();

        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_deadline && $now->gt($this->registration_deadline)) {
            return false;
        }

        return true;
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->filledCount());
    }

    public function hasCapacityFor(int $seats = 1): bool
    {
        if ($this->capacity === null) {
            return true;
        }

        return $this->filledCount() + $seats <= $this->capacity;
    }

    /**
     * Number of active (non-cancelled) registrations.
     *
     * Reuses the counter loaded by `withCount('activeRegistrations')` when it is
     * available so that capacity checks do not issue an extra query.
     */
    public function filledCount(): int
    {
        if (array_key_exists('active_registrations_count', $this->attributes)) {
            return (int) $this->attributes['active_registrations_count'];
        }

        return $this->activeRegistrations()->count();
    }
}
