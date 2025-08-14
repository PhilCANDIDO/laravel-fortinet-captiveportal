<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupOldAuditLogs implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        // Get retention days from settings, default to 90 days
        $retentionDays = Setting::get('audit_retention_days', 90);
        
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        // Delete old audit logs
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        // Log the cleanup action
        if ($deletedCount > 0) {
            AuditLog::log([
                'event_type' => 'system_maintenance',
                'event_category' => 'system',
                'action' => 'cleanup_audit_logs',
                'status' => 'success',
                'metadata' => [
                    'deleted_count' => $deletedCount,
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ],
            ]);
        }
    }
}