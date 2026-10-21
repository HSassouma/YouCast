<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(?User $user, string $action, Model $auditable, array $meta = []): AuditLog
    {
        return AuditLog::record($user, $action, $auditable, $meta);
    }
}
