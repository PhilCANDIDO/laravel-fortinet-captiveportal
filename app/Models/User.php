<?php

namespace App\Models;

use App\Traits\SyncsWithFortiGate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SyncsWithFortiGate;

    // User types
    const TYPE_EMPLOYEE = 'employee';
    const TYPE_CONSULTANT = 'consultant';
    const TYPE_GUEST = 'guest';
    
    // User status
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DELETED = 'deleted';
    
    // FortiGate sync status
    const SYNC_PENDING = 'pending';
    const SYNC_SYNCED = 'synced';
    const SYNC_ERROR = 'error';
    const SYNC_NOT_REQUIRED = 'not_required';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'company_name',
        'department',
        'sponsor_email',
        'sponsor_name',
        'phone',
        'mobile',
        'validation_token',
        'validation_expires_at',
        'validated_at',
        'expires_at',
        'is_active',
        'charter_accepted_at',
        'charter_version',
        'fortigate_username',
        'fortigate_sync_status',
        'fortigate_synced_at',
        'fortigate_sync_error',
        'registration_ip',
        'last_login_ip',
        'last_login_at',
        'login_count',
        'status',
        'status_reason',
        'admin_notes',
        'portal_data',
        'network_ssid',
        'visit_reason',
        'first_name',
        'last_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'validation_token',
    ];
    
    /**
     * Temporary password (not persisted to database)
     * @var string|null
     */
    public $temp_password;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'validation_expires_at' => 'datetime',
            'validated_at' => 'datetime',
            'expires_at' => 'datetime',
            'charter_accepted_at' => 'datetime',
            'fortigate_synced_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'login_count' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Generate FortiGate username if not set
            if (!$user->fortigate_username) {
                $user->fortigate_username = $user->generateFortiGateUsername();
            }
            
            // Set expiration dates based on user type
            if (!$user->expires_at) {
                $user->setDefaultExpiration();
            }
            
            // Generate validation token for guests
            if ($user->user_type === self::TYPE_GUEST && !$user->validation_token) {
                $user->generateValidationToken();
            }
        });
        
        static::updating(function ($user) {
            // Mark for FortiGate sync if relevant fields changed
            if ($user->isDirty(['name', 'email', 'password', 'is_active', 'expires_at'])) {
                $user->fortigate_sync_status = self::SYNC_PENDING;
            }
        });
    }

    /**
     * Check if user is an employee
     */
    public function isEmployee(): bool
    {
        return $this->user_type === self::TYPE_EMPLOYEE;
    }

    /**
     * Check if user is a consultant
     */
    public function isConsultant(): bool
    {
        return $this->user_type === self::TYPE_CONSULTANT;
    }

    /**
     * Check if user is a guest
     */
    public function isGuest(): bool
    {
        return $this->user_type === self::TYPE_GUEST;
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->is_active 
            && $this->status === self::STATUS_ACTIVE
            && !$this->isExpired();
    }

    /**
     * Check if user account is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // No expiration date (employees)
        }
        
        return $this->expires_at->isPast();
    }

    /**
     * Check if user needs email validation
     */
    public function needsValidation(): bool
    {
        return $this->user_type === self::TYPE_GUEST 
            && !$this->validated_at
            && $this->validation_expires_at
            && $this->validation_expires_at->isFuture();
    }

    /**
     * Check if validation has expired
     */
    public function validationExpired(): bool
    {
        return $this->validation_expires_at 
            && $this->validation_expires_at->isPast();
    }

    /**
     * Check if user has accepted charter
     */
    public function hasAcceptedCharter(): bool
    {
        return $this->charter_accepted_at !== null;
    }

    /**
     * Check if user needs FortiGate sync
     */
    public function needsFortiGateSync(): bool
    {
        return $this->fortigate_sync_status === self::SYNC_PENDING
            || $this->fortigate_sync_status === self::SYNC_ERROR;
    }

    /**
     * Generate FortiGate username
     */
    public function generateFortiGateUsername(): string
    {
        // For guests, use guest-{id} pattern
        if ($this->user_type === self::TYPE_GUEST) {
            // If ID is not set yet (new record), we'll update it after save
            return $this->id ? "guest-{$this->id}" : "guest-pending";
        }
        
        // For other types, keep the existing pattern
        $prefix = match($this->user_type) {
            self::TYPE_EMPLOYEE => 'emp',
            self::TYPE_CONSULTANT => 'con',
            default => 'usr',
        };
        
        // Use email prefix or name
        $base = Str::before($this->email, '@') ?: Str::slug($this->name);
        $base = Str::limit($base, 10, '');
        
        // Add random suffix to ensure uniqueness
        $suffix = strtolower(Str::random(4));
        
        return "{$prefix}_{$base}_{$suffix}";
    }

    /**
     * Generate validation token for email validation
     */
    public function generateValidationToken(): void
    {
        $this->validation_token = Str::random(64);
        $this->validation_expires_at = Carbon::now()->addMinutes(30);
    }

    /**
     * Validate the user's email
     */
    public function validateEmail(): bool
    {
        if ($this->validationExpired()) {
            return false;
        }
        
        $this->validated_at = Carbon::now();
        $this->validation_token = null;
        $this->validation_expires_at = null;
        $this->status = self::STATUS_ACTIVE;
        $this->is_active = true;
        
        return $this->save();
    }

    /**
     * Accept charter
     */
    public function acceptCharter(string $version = '1.0'): void
    {
        $this->charter_accepted_at = Carbon::now();
        $this->charter_version = $version;
        $this->save();
    }

    /**
     * Set default expiration based on user type
     */
    public function setDefaultExpiration(): void
    {
        $this->expires_at = match($this->user_type) {
            self::TYPE_GUEST => Carbon::now()->addDay(), // 24 hours for guests
            self::TYPE_CONSULTANT => Carbon::now()->addMonth(), // 1 month default for consultants
            self::TYPE_EMPLOYEE => null, // No expiration for employees
            default => null,
        };
    }

    /**
     * Extend expiration date
     */
    public function extendExpiration(int $days): void
    {
        if ($this->user_type === self::TYPE_EMPLOYEE) {
            return; // Employees don't expire
        }
        
        $currentExpiration = $this->expires_at ?: Carbon::now();
        $this->expires_at = $currentExpiration->addDays($days);
        $this->save();
    }

    /**
     * Suspend user account
     */
    public function suspend(string $reason = null): void
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->status_reason = $reason;
        $this->is_active = false;
        $this->fortigate_sync_status = self::SYNC_PENDING;
        $this->save();
    }

    /**
     * Reactivate user account
     */
    public function reactivate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->status_reason = null;
        $this->is_active = true;
        $this->fortigate_sync_status = self::SYNC_PENDING;
        $this->save();
    }

    /**
     * Mark account as expired
     */
    public function markAsExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->is_active = false;
        $this->fortigate_sync_status = self::SYNC_PENDING;
        $this->save();
    }

    /**
     * Update FortiGate sync status
     */
    public function updateFortiGateSync(string $status, string $error = null): void
    {
        // Update the database directly to avoid saving temp_password
        $updates = [
            'fortigate_sync_status' => $status,
            'fortigate_sync_error' => $error,
        ];
        
        if ($status === self::SYNC_SYNCED) {
            $updates['fortigate_synced_at'] = Carbon::now();
        }
        
        // Update only the specific fields in the database
        self::where('id', $this->id)->update($updates);
        
        // Update the model attributes too
        $this->fortigate_sync_status = $status;
        $this->fortigate_sync_error = $error;
        if ($status === self::SYNC_SYNCED) {
            $this->fortigate_synced_at = Carbon::now();
        }
    }

    /**
     * Update last login information
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->last_login_at = Carbon::now();
        $this->last_login_ip = $ip;
        $this->login_count = $this->login_count + 1;
        $this->save();
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('status', self::STATUS_ACTIVE)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', Carbon::now());
                     });
    }

    /**
     * Scope for expired users
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope for users needing validation
     */
    public function scopeNeedingValidation($query)
    {
        return $query->where('user_type', self::TYPE_GUEST)
                     ->whereNull('validated_at')
                     ->whereNotNull('validation_expires_at')
                     ->where('validation_expires_at', '>', Carbon::now());
    }

    /**
     * Scope for users with expired validation
     */
    public function scopeValidationExpired($query)
    {
        return $query->where('user_type', self::TYPE_GUEST)
                     ->whereNull('validated_at')
                     ->whereNotNull('validation_expires_at')
                     ->where('validation_expires_at', '<=', Carbon::now());
    }

    /**
     * Scope for users needing FortiGate sync
     */
    public function scopeNeedingSync($query)
    {
        return $query->whereIn('fortigate_sync_status', [self::SYNC_PENDING, self::SYNC_ERROR]);
    }

    /**
     * Get user type label
     */
    public function getUserTypeLabel(): string
    {
        return __('fortigate.user_types.' . $this->user_type);
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return __('users.status.' . $this->status);
    }

    /**
     * Get sync status label
     */
    public function getSyncStatusLabel(): string
    {
        return __('fortigate.status.' . $this->fortigate_sync_status);
    }
}