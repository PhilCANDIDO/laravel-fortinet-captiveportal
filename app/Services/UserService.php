<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\DeleteUnvalidatedGuestJob;
use App\Jobs\SyncFortiGateUserJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserService
{
    protected AuditService $auditService;
    protected FortiGateService $fortiGateService;
    
    public function __construct(
        AuditService $auditService,
        FortiGateService $fortiGateService
    ) {
        $this->auditService = $auditService;
        $this->fortiGateService = $fortiGateService;
    }
    
    /**
     * Create an employee account
     */
    public function createEmployee(array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Generate secure password if not provided
            if (!isset($data['password'])) {
                $data['password'] = $this->generateSecurePassword();
                $data['generated_password'] = $data['password']; // Store for email
            }
            
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'user_type' => User::TYPE_EMPLOYEE,
                'company_name' => $data['company_name'] ?? null,
                'department' => $data['department'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'is_active' => true,
                'status' => User::STATUS_ACTIVE,
                'registration_ip' => request()->ip(),
            ]);
            
            // Sync with FortiGate immediately for employees
            if ($this->fortiGateService->isConfigured()) {
                dispatch(new SyncFortiGateUserJob($user->id));
            }
            
            // Log the action
            $this->auditService->log(
                'employee_created',
                "Employee account created: {$user->email}",
                ['user_id' => $user->id, 'name' => $user->name]
            );
            
            DB::commit();
            
            return $user;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a consultant account
     */
    public function createConsultant(array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Generate secure password if not provided
            if (!isset($data['password'])) {
                $data['password'] = $this->generateSecurePassword();
                $data['generated_password'] = $data['password'];
            }
            
            // Set expiration date
            $expiresAt = isset($data['expires_at']) 
                ? Carbon::parse($data['expires_at'])
                : Carbon::now()->addMonth();
            
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'user_type' => User::TYPE_CONSULTANT,
                'company_name' => $data['company_name'],
                'department' => $data['department'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'status' => User::STATUS_ACTIVE,
                'registration_ip' => request()->ip(),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);
            
            // Sync with FortiGate
            if ($this->fortiGateService->isConfigured()) {
                dispatch(new SyncFortiGateUserJob($user->id));
            }
            
            // Log the action
            $this->auditService->log(
                'consultant_created',
                "Consultant account created: {$user->email}",
                [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'expires_at' => $expiresAt->format('Y-m-d'),
                ]
            );
            
            DB::commit();
            
            return $user;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create consultant', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a guest account
     */
    public function createGuest(array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Check for duplicate email
            $existingUser = User::where('email', $data['email'])
                ->where('user_type', User::TYPE_GUEST)
                ->first();
                
            if ($existingUser) {
                throw new \Exception('Email already registered');
            }
            
            // Generate secure password
            $password = $this->generateSecurePassword();
            
            // Guests expire after 24 hours
            $expiresAt = Carbon::now()->addDay();
            
            // Determine if email validation is enabled
            $emailValidationEnabled = \App\Models\Setting::isGuestEmailValidationEnabled();
            
            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $data['email'],
                'password' => Hash::make($password),
                'user_type' => User::TYPE_GUEST,
                'company_name' => $data['company_name'] ?? null,
                'sponsor_name' => null,
                'sponsor_email' => null,
                'phone' => $data['phone'] ?? null,
                'expires_at' => $expiresAt,
                'is_active' => !$emailValidationEnabled, // Active immediately if validation disabled
                'status' => $emailValidationEnabled ? User::STATUS_PENDING : User::STATUS_ACTIVE,
                'registration_ip' => request()->ip(),
            ]);
            
            // Update FortiGate username with the actual ID
            $user->fortigate_username = "guest-{$user->id}";
            $user->save();
            
            // Create FortiGate user immediately (enabled if validation disabled)
            try {
                if (app(FortiGateService::class)->isConfigured()) {
                    $userData = [
                        'username' => $user->fortigate_username,
                        'password' => $password,
                        'email' => $user->email,
                        'expires_at' => $user->expires_at ? $user->expires_at->format('Y-m-d H:i:s') : null,
                        'status' => $emailValidationEnabled ? 'disable' : 'enable',
                    ];
                    
                    app(FortiGateService::class)->createUser($userData);
                    $user->fortigate_sync_status = User::SYNC_SYNCED;
                    $user->fortigate_synced_at = now();
                    $user->save();
                }
            } catch (\Exception $e) {
                Log::warning('Could not create FortiGate user during registration: ' . $e->getMessage());
                // Don't fail the registration if FortiGate sync fails
            }
            
            // Only schedule validation job if email validation is enabled
            if ($emailValidationEnabled) {
                // Schedule disabling if not validated within configured delay
                $delayMinutes = \App\Models\Setting::getGuestValidationDelayMinutes();
                DeleteUnvalidatedGuestJob::dispatch($user->id)
                    ->delay(now()->addMinutes($delayMinutes));
            }
            
            // Log the action
            $this->auditService->log(
                'guest_created',
                "Guest account created: {$user->email}",
                [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'company' => $data['company_name'] ?? null,
                ]
            );
            
            DB::commit();
            
            // Add password to user object for email (not saved in DB)
            $user->temp_password = $password;
            
            return $user;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create guest', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
    
    /**
     * Update user
     */
    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();
        
        try {
            $oldData = $user->toArray();
            
            // Update allowed fields based on user type
            $fillable = [
                'name', 'email', 'company_name', 'department',
                'phone', 'mobile', 'admin_notes'
            ];
            
            // Consultants can have expiration updated
            if ($user->isConsultant() && isset($data['expires_at'])) {
                $data['expires_at'] = Carbon::parse($data['expires_at']);
            }
            
            // Update password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            
            $user->update(array_intersect_key($data, array_flip($fillable)));
            
            // Log the action
            $this->auditService->log(
                'user_updated',
                "User updated: {$user->email}",
                [
                    'user_id' => $user->id,
                    'changes' => array_diff_assoc($user->toArray(), $oldData),
                ]
            );
            
            DB::commit();
            
            return $user;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $data,
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate guest email
     */
    public function validateGuestEmail(string $token): ?User
    {
        $user = User::where('validation_token', $token)
                    ->where('user_type', User::TYPE_GUEST)
                    ->first();
        
        if (!$user) {
            Log::warning('Invalid validation token used', ['token' => $token]);
            return null;
        }
        
        if ($user->validationExpired()) {
            Log::info('Validation token expired', ['user_id' => $user->id]);
            return null;
        }
        
        if ($user->validated_at) {
            Log::info('User already validated', ['user_id' => $user->id]);
            return $user;
        }
        
        // Validate the email
        $user->validateEmail();
        
        // Sync with FortiGate now that user is validated
        if ($this->fortiGateService->settings->is_active) {
            dispatch(new SyncFortiGateUserJob($user->id));
        }
        
        // Log the action
        $this->auditService->log(
            'guest_validated',
            "Guest email validated: {$user->email}",
            ['user_id' => $user->id]
        );
        
        return $user;
    }
    
    /**
     * Extend user expiration
     */
    public function extendExpiration(User $user, int $days): User
    {
        if ($user->isEmployee()) {
            throw new \Exception('Employee accounts do not expire');
        }
        
        $oldExpiration = $user->expires_at;
        $user->extendExpiration($days);
        
        // Sync with FortiGate
        if ($this->fortiGateService->settings->is_active) {
            dispatch(new SyncFortiGateUserJob($user->id));
        }
        
        // Log the action
        $this->auditService->log(
            'expiration_extended',
            "User expiration extended: {$user->email}",
            [
                'user_id' => $user->id,
                'old_expiration' => $oldExpiration?->format('Y-m-d'),
                'new_expiration' => $user->expires_at->format('Y-m-d'),
                'days_added' => $days,
            ]
        );
        
        return $user;
    }
    
    /**
     * Suspend user
     */
    public function suspendUser(User $user, string $reason = null): User
    {
        $user->suspend($reason);
        
        // Remove from FortiGate
        if ($this->fortiGateService->settings->is_active) {
            $user->removeFromFortiGate();
        }
        
        // Log the action
        $this->auditService->log(
            'user_suspended',
            "User suspended: {$user->email}",
            [
                'user_id' => $user->id,
                'reason' => $reason,
            ]
        );
        
        return $user;
    }
    
    /**
     * Reactivate user
     */
    public function reactivateUser(User $user): User
    {
        $user->reactivate();
        
        // Sync with FortiGate
        if ($this->fortiGateService->settings->is_active) {
            dispatch(new SyncFortiGateUserJob($user->id));
        }
        
        // Log the action
        $this->auditService->log(
            'user_reactivated',
            "User reactivated: {$user->email}",
            ['user_id' => $user->id]
        );
        
        return $user;
    }
    
    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        DB::beginTransaction();
        
        try {
            // Remove from FortiGate first
            if ($this->fortiGateService->isConfigured()) {
                $user->removeFromFortiGate();
            }
            
            // Log before deletion
            $this->auditService->log(
                'user_deleted',
                "User deleted: {$user->email}",
                [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'name' => $user->name,
                ]
            );
            
            // Delete the user
            $user->delete();
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            throw $e;
        }
    }
    
    /**
     * Check and handle expired users
     */
    public function handleExpiredUsers(): int
    {
        $expiredUsers = User::expired()
                            ->where('status', '!=', User::STATUS_EXPIRED)
                            ->get();
        
        $count = 0;
        
        foreach ($expiredUsers as $user) {
            $user->markAsExpired();
            
            // Remove from FortiGate
            if ($this->fortiGateService->isConfigured()) {
                $user->removeFromFortiGate();
            }
            
            // Log the action
            $this->auditService->log(
                'user_expired',
                "User account expired: {$user->email}",
                ['user_id' => $user->id]
            );
            
            $count++;
        }
        
        if ($count > 0) {
            Log::info("Processed {$count} expired users");
        }
        
        return $count;
    }
    
    /**
     * Clean up unvalidated guests
     */
    public function cleanupUnvalidatedGuests(): int
    {
        $unvalidatedGuests = User::validationExpired()
                                  ->whereNull('validated_at')
                                  ->get();
        
        $count = 0;
        
        foreach ($unvalidatedGuests as $guest) {
            $this->deleteUser($guest);
            $count++;
        }
        
        if ($count > 0) {
            Log::info("Deleted {$count} unvalidated guests");
        }
        
        return $count;
    }
    
    /**
     * Sync all pending users with FortiGate
     */
    public function syncPendingUsers(): int
    {
        if (!$this->fortiGateService->isConfigured()) {
            return 0;
        }
        
        $pendingUsers = User::needingSync()->get();
        $count = 0;
        
        foreach ($pendingUsers as $user) {
            if ($user->syncWithFortiGate()) {
                $count++;
            }
        }
        
        if ($count > 0) {
            Log::info("Synced {$count} users with FortiGate");
        }
        
        return $count;
    }
    
    /**
     * Generate secure password
     */
    public function generateSecurePassword(int $length = null): string
    {
        $settings = \App\Models\FortiGateSettings::current();
        $length = $length ?? $settings->default_password_length ?? 12;
        
        // Ensure ANSSI compliance (min 12 chars)
        $length = max(12, $length);
        
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one of each type for complexity
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle to avoid predictable patterns
        $password = str_shuffle($password);
        
        return $password;
    }
    
    /**
     * Generate username for FortiGate
     */
    public function generateUsername(string $email, string $userType): string
    {
        $prefix = match($userType) {
            User::TYPE_EMPLOYEE => 'emp',
            User::TYPE_CONSULTANT => 'con',
            User::TYPE_GUEST => 'gst',
            default => 'usr',
        };
        
        $base = Str::before($email, '@');
        $base = Str::slug($base);
        $base = Str::limit($base, 10, '');
        
        // Check for uniqueness
        $username = "{$prefix}_{$base}";
        $counter = 1;
        
        while (User::where('fortigate_username', $username)->exists()) {
            $username = "{$prefix}_{$base}_{$counter}";
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'by_type' => [
                'employees' => User::where('user_type', User::TYPE_EMPLOYEE)->count(),
                'consultants' => User::where('user_type', User::TYPE_CONSULTANT)->count(),
                'guests' => User::where('user_type', User::TYPE_GUEST)->count(),
            ],
            'by_status' => [
                'active' => User::active()->count(),
                'pending' => User::where('status', User::STATUS_PENDING)->count(),
                'suspended' => User::where('status', User::STATUS_SUSPENDED)->count(),
                'expired' => User::where('status', User::STATUS_EXPIRED)->count(),
            ],
            'sync_status' => [
                'synced' => User::where('fortigate_sync_status', User::SYNC_SYNCED)->count(),
                'pending' => User::where('fortigate_sync_status', User::SYNC_PENDING)->count(),
                'error' => User::where('fortigate_sync_status', User::SYNC_ERROR)->count(),
            ],
            'expiring_soon' => User::where('expires_at', '>', now())
                                   ->where('expires_at', '<=', now()->addDays(7))
                                   ->count(),
            'awaiting_validation' => User::needingValidation()->count(),
        ];
    }
}