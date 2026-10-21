<?php

namespace App\Models;

use App\Enums\IdeaStatus;
use Database\Factories\ChallengeIdeaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChallengeIdea extends Model
{
    /** @use HasFactory<ChallengeIdeaFactory> */
    use HasFactory;

    /**
     * Only the learner-authored content may be mass-assigned.
     *
     * Ownership (`author_id`, `reviewer_id`) and moderation state (`status`,
     * `review_notes`, `reviewed_at`) are assigned explicitly by server-side
     * code so a request payload can never reach them.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'status' => IdeaStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class, 'source_idea_id');
    }

    public function isPending(): bool
    {
        return $this->status === IdeaStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === IdeaStatus::Accepted;
    }
}
