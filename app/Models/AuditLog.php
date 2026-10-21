<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /**
     * Audit entries are written exclusively by the audit logger. Nothing may be
     * mass assigned.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(?User $user, string $action, Model $auditable, array $meta = []): self
    {
        $log = new self;
        $log->user_id = $user?->id;
        $log->action = $action;
        $log->auditable_type = $auditable->getMorphClass();
        $log->auditable_id = $auditable->getKey();
        $log->meta = $meta ?: null;
        $log->save();

        return $log;
    }
}
