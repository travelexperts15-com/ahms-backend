<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Write an activity log entry.
     *
     * @param  string      $event       Machine-readable event key  e.g. "auth.login"
     * @param  string      $description Human-readable description
     * @param  int|null    $userId      Who triggered the event (null for system)
     * @param  array       $properties  Extra JSON payload
     */
    public function log(
        string $event,
        string $description,
        ?int   $userId     = null,
        array  $properties = []
    ): void {
        try {
            ActivityLog::create([
                'user_id'      => $userId,
                'event'        => $event,
                'description'  => $description,
                'ip_address'   => Request::ip(),
                'user_agent'   => Request::userAgent(),
                'properties'   => empty($properties) ? null : json_encode($properties),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main request
            \Log::warning("AuditService failed: {$e->getMessage()}");
        }
    }
}
