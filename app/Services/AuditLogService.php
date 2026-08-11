<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public static function log(
        string $action,
        string $module,
        ?int $userId = null,
        ?int $recordId = null,
        ?array $payload = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'ip_address' => $ipAddress ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
            'payload' => $payload,
        ]);
    }
}
