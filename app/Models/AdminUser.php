<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'google2fa_secret',
        'google2fa_backup_codes',
        'google2fa_enabled',
        'google2fa_enabled_at',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'password_expires_at',
        'last_ip',
        'last_login_at',
        'is_active',
        'must_change_password',
        'is_first_login',
    ];
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'google2fa_backup_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'google2fa_enabled' => 'boolean',
        'google2fa_enabled_at' => 'datetime',
        'google2fa_backup_codes' => 'array',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'is_first_login' => 'boolean',
    ];

    public function passwordHistories()
    {
        return $this->hasMany(AdminPasswordHistory::class);
    }

    public function sessions()
    {
        return $this->hasMany(AdminSession::class);
    }

    public function isAccountLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => Carbon::now()->addMinutes($minutes),
        ]);
    }

    public function unlockAccount(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }

    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
        ]);
    }

    public function isPasswordExpired(): bool
    {
        return $this->password_expires_at && $this->password_expires_at->isPast();
    }
    
    public function isInMfaGracePeriod(): bool
    {
        // Give 7 days grace period for MFA setup after account creation
        if (!$this->google2fa_enabled && $this->created_at) {
            return $this->created_at->addDays(7)->isFuture();
        }
        return false;
    }
    
    public function shouldEnforceMfa(): bool
    {
        // MFA is mandatory but give grace period for new accounts
        return !$this->google2fa_enabled && !$this->isInMfaGracePeriod();
    }

    public function setPasswordAttribute($value): void
    {
        // Only hash if the value isn't already hashed
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
        
        // Only set timestamps if they're not already set
        if (!isset($this->attributes['password_changed_at'])) {
            $this->attributes['password_changed_at'] = Carbon::now();
        }
        if (!isset($this->attributes['password_expires_at'])) {
            $this->attributes['password_expires_at'] = Carbon::now()->addDays(90);
        }
    }

    public function hasUsedPassword(string $password): bool
    {
        $recentPasswords = $this->passwordHistories()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        foreach ($recentPasswords as $history) {
            if (Hash::check($password, $history->password)) {
                return true;
            }
        }

        return false;
    }

    public function savePasswordToHistory(): void
    {
        $this->passwordHistories()->create([
            'password' => $this->password,
        ]);

        $this->passwordHistories()
            ->orderBy('created_at', 'desc')
            ->skip(10)
            ->delete();
    }

    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(Str::random(8) . '-' . Str::random(8));
        }
        
        $this->update([
            'google2fa_backup_codes' => array_map(fn($code) => Hash::make($code), $codes),
        ]);
        
        return $codes;
    }

    public function useBackupCode(string $code): bool
    {
        if (!$this->google2fa_backup_codes) {
            return false;
        }

        $codes = $this->google2fa_backup_codes;
        
        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                unset($codes[$index]);
                $this->update([
                    'google2fa_backup_codes' => array_values($codes),
                ]);
                return true;
            }
        }
        
        return false;
    }

    public function hasRemainingBackupCodes(): bool
    {
        return !empty($this->google2fa_backup_codes);
    }

    public function getBackupCodesCount(): int
    {
        return $this->google2fa_backup_codes ? count($this->google2fa_backup_codes) : 0;
    }

    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_ip' => $ipAddress,
            'last_login_at' => Carbon::now(),
            'failed_login_attempts' => 0,
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
        ]);
        
        $this->sessions()->update([
            'is_active' => false,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
        ]);
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
    
    public function canResetMfaFor(AdminUser $user): bool
    {
        // Only super admins can reset MFA for other users
        return $this->isSuperAdmin() && $this->id !== $user->id;
    }
    
    public function resetMfa(): void
    {
        $this->update([
            'google2fa_secret' => null,
            'google2fa_enabled' => false,
            'google2fa_enabled_at' => null,
            'google2fa_backup_codes' => null,
        ]);
    }
}