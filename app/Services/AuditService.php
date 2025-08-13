<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Exports\AuditLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AuditService
{
    /**
     * Generic logging method for custom events
     */
    public static function log(string $eventType, string $eventCategory, array $metadata = []): void
    {
        $user = auth('admin')->user();
        
        AuditLog::log([
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'user_type' => $user ? 'admin' : 'system',
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $eventType,
            'status' => 'success',
            'metadata' => $metadata,
        ]);
    }

    public function logLogin(string $email, bool $success, ?array $metadata = null): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['LOGIN'],
            $success ? AuditLog::STATUS['SUCCESS'] : AuditLog::STATUS['FAILURE'],
            array_merge($metadata ?? [], ['email' => $email])
        );
    }
    
    public function logLogout(): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['LOGOUT'],
            AuditLog::STATUS['SUCCESS']
        );
    }
    
    public function logFailedLogin(string $email, string $reason): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['LOGIN_FAILED'],
            AuditLog::STATUS['FAILURE'],
            ['email' => $email, 'reason' => $reason]
        );
    }
    
    public function logAccountLocked(string $email, int $attempts): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['ACCOUNT_LOCKED'],
            AuditLog::STATUS['WARNING'],
            ['email' => $email, 'failed_attempts' => $attempts]
        );
    }
    
    public function logAccountUnlocked(string $email): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['ACCOUNT_UNLOCKED'],
            AuditLog::STATUS['SUCCESS'],
            ['email' => $email]
        );
    }
    
    public function logPasswordChanged(string $email): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['PASSWORD_CHANGED'],
            AuditLog::STATUS['SUCCESS'],
            ['email' => $email]
        );
    }
    
    public function logPasswordReset(string $email): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['PASSWORD_RESET'],
            AuditLog::STATUS['SUCCESS'],
            ['email' => $email]
        );
    }
    
    public function logMfaEnabled(string $email): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['MFA_ENABLED'],
            AuditLog::STATUS['SUCCESS'],
            ['email' => $email]
        );
    }
    
    public function logMfaDisabled(string $email): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['MFA_DISABLED'],
            AuditLog::STATUS['WARNING'],
            ['email' => $email]
        );
    }
    
    public function logMfaVerified(string $email, string $method): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['MFA_VERIFIED'],
            AuditLog::STATUS['SUCCESS'],
            ['email' => $email, 'method' => $method]
        );
    }
    
    public function logMfaFailed(string $email, string $method): void
    {
        AuditLog::logAuthentication(
            AuditLog::EVENT_TYPES['MFA_FAILED'],
            AuditLog::STATUS['FAILURE'],
            ['email' => $email, 'method' => $method]
        );
    }
    
    public function logUserCreated(string $resourceType, int $resourceId, array $data): void
    {
        AuditLog::logUserManagement('created', $resourceType, $resourceId, null, $data);
    }
    
    public function logUserUpdated(string $resourceType, int $resourceId, array $oldData, array $newData): void
    {
        AuditLog::logUserManagement('updated', $resourceType, $resourceId, $oldData, $newData);
    }
    
    public function logUserDeleted(string $resourceType, int $resourceId, array $data): void
    {
        AuditLog::logUserManagement('deleted', $resourceType, $resourceId, $data, null);
    }
    
    public function logDataExport(string $exportType, array $filters = []): void
    {
        $user = auth('admin')->user();
        
        AuditLog::log([
            'event_type' => AuditLog::EVENT_TYPES['DATA_EXPORTED'],
            'event_category' => AuditLog::EVENT_CATEGORIES['DATA_ACCESS'],
            'user_type' => 'admin',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => 'export',
            'status' => AuditLog::STATUS['SUCCESS'],
            'metadata' => [
                'export_type' => $exportType,
                'filters' => $filters,
            ],
        ]);
    }
    
    public function logSettingsUpdate(string $key, $oldValue, $newValue): void
    {
        $user = auth('admin')->user();
        
        AuditLog::log([
            'event_type' => AuditLog::EVENT_TYPES['SETTINGS_UPDATED'],
            'event_category' => AuditLog::EVENT_CATEGORIES['SYSTEM'],
            'user_type' => 'admin',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => 'update_settings',
            'status' => AuditLog::STATUS['SUCCESS'],
            'metadata' => [
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
        ]);
    }
    
    public function exportToExcel(array $filters = [], string $filename = null): string
    {
        $filename = $filename ?? 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        $this->logDataExport('audit_logs', $filters);
        
        return Excel::download(new AuditLogExport($filters), $filename)->getFile()->getPathname();
    }
    
    public function cleanupOldLogs(int $retentionDays = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }
    
    public function getRecentFailedLogins(int $minutes = 30): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('event_type', AuditLog::EVENT_TYPES['LOGIN_FAILED'])
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function getSuspiciousActivity(string $ipAddress = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::whereIn('event_type', [
            AuditLog::EVENT_TYPES['LOGIN_FAILED'],
            AuditLog::EVENT_TYPES['MFA_FAILED'],
            AuditLog::EVENT_TYPES['ACCOUNT_LOCKED'],
        ]);
        
        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }
        
        return $query->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}