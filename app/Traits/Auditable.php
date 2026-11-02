<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Get the audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, "auditable")->latest();
    }

    /**
     * Record an audit log entry.
     *
     * @param string $action Action name (e.g., "status_changed", "checklist_completed")
     * @param array|null $oldValues Previous values
     * @param array|null $newValues New values
     * @param Request|null $request Optional request for IP address
     */
    protected function recordAudit(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): void {
        $user = Auth::user();

        AuditLog::create([
            "auditable_type" => static::class,
            "auditable_id" => $this->getKey(),
            "user_id" => $user?->id,
            "action" => $action,
            "old_values" => $oldValues,
            "new_values" => $newValues,
            "ip_address" => $request?->ip(),
        ]);
    }
}
