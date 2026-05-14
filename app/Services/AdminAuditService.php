<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminAuditService
{
    public function record(
        Request $request,
        User $targetUser,
        string $action,
        ?Model $resource = null,
        array $metadata = [],
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'admin_user_id' => $request->user()?->id,
            'target_user_id' => $targetUser->id,
            'action' => $action,
            'resource_type' => $resource ? $resource::class : null,
            'resource_id' => $resource?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
