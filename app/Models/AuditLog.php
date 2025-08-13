<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'event_category',
        'user_type',
        'user_id',
        'user_email',
        'ip_address',
        'user_agent',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'metadata',
        'status',
        'message',
        'session_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    const EVENT_TYPES = [
        'LOGIN' => 'login',
        'LOGOUT' => 'logout',
        'LOGIN_FAILED' => 'login_failed',
        'ACCOUNT_LOCKED' => 'account_locked',
        'ACCOUNT_UNLOCKED' => 'account_unlocked',
        'PASSWORD_CHANGED' => 'password_changed',
        'PASSWORD_RESET' => 'password_reset',
        'MFA_ENABLED' => 'mfa_enabled',
        'MFA_DISABLED' => 'mfa_disabled',
        'MFA_VERIFIED' => 'mfa_verified',
        'MFA_FAILED' => 'mfa_failed',
        'USER_CREATED' => 'user_created',
        'USER_UPDATED' => 'user_updated',
        'USER_DELETED' => 'user_deleted',
        'PERMISSION_GRANTED' => 'permission_granted',
        'PERMISSION_REVOKED' => 'permission_revoked',
        'SETTINGS_UPDATED' => 'settings_updated',
        'DATA_EXPORTED' => 'data_exported',
    ];

    const EVENT_CATEGORIES = [
        'AUTHENTICATION' => 'authentication',
        'AUTHORIZATION' => 'authorization',
        'USER_MANAGEMENT' => 'user_management',
        'SYSTEM' => 'system',
        'DATA_ACCESS' => 'data_access',
    ];

    const STATUS = [
        'SUCCESS' => 'success',
        'FAILURE' => 'failure',
        'WARNING' => 'warning',
    ];

    public function scopeByUser($query, $userType, $userId)
    {
        return $query->where('user_type', $userType)
                    ->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public static function log(array $data): self
    {
        return self::create(array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ], $data));
    }

    public static function logAuthentication(string $eventType, string $status, ?array $metadata = null): self
    {
        $user = auth('admin')->user();
        
        return self::log([
            'event_type' => $eventType,
            'event_category' => self::EVENT_CATEGORIES['AUTHENTICATION'],
            'user_type' => $user ? 'admin' : null,
            'user_id' => $user?->id,
            'user_email' => $user?->email ?? request()->input('email'),
            'action' => $eventType,
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }

    public static function logUserManagement(string $action, string $resourceType, ?int $resourceId, ?array $oldValues = null, ?array $newValues = null): self
    {
        $user = auth('admin')->user();
        
        return self::log([
            'event_type' => self::EVENT_TYPES['USER_' . strtoupper($action)],
            'event_category' => self::EVENT_CATEGORIES['USER_MANAGEMENT'],
            'user_type' => 'admin',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => self::STATUS['SUCCESS'],
        ]);
    }
}