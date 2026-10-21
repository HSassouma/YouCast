<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * Only the learner supplied subject may be mass-assigned. The owner
     * (`student_id`), the workflow state (`status`) and the activity counters
     * are assigned explicitly by the conversation service.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subject',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation): void {
            if (blank($conversation->status)) {
                $conversation->status = ConversationStatus::Open;
            }
        });
    }

    /**
     * The learner who opened the conversation.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The most recent message of the conversation.
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ConversationStatus::Open->value);
    }

    public function isOpen(): bool
    {
        return $this->status === ConversationStatus::Open;
    }

    /**
     * Determine whether the given account takes part in the conversation.
     *
     * In this workflow the participants are the learner who opened the
     * conversation and the BDE administrators, who answer from the protected
     * administration area. Every read and write is checked against this.
     */
    public function hasParticipant(User $user): bool
    {
        return $user->isAdmin() || (int) $this->student_id === (int) $user->id;
    }
}
