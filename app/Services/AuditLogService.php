<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Record an audit log entry.
     *
     * This is the centralized mechanism for all audit logging across the system.
     * It must be called AFTER the business action succeeds (inside the same
     * transaction when applicable) so that rolled-back actions do not leave
     * orphan audit records.
     */
    public static function log(
        string $action,
        string $module,
        ?string $recordType = null,
        ?int $recordId = null,
        ?string $description = null,
        ?int $userId = null,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id'     => $userId ?? auth()->id(),
            'action'      => $action,
            'module'      => $module,
            'record_type' => $recordType,
            'record_id'   => $recordId,
            'description' => $description,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request?->userAgent(),
            'created_at'  => now(),
        ]);
    }
}