<?php

namespace App\Models;

use App\Enums\TeamMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    /**
     * A membership is fully owned by the server: which team, which learner and
     * which role (a privilege) are all assigned explicitly by the team
     * service. Nothing may be mass assigned.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'role' => TeamMemberRole::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function isLeader(): bool
    {
        return $this->role === TeamMemberRole::Leader;
    }
}
