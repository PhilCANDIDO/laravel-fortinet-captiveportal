<?php

namespace App\Traits;

use App\Services\FortiGateService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

trait SyncsWithFortiGate
{
    /**
     * Sync user with FortiGate
     */
    public function syncWithFortiGate(): bool
    {
        try {
            $fortiGateService = app(FortiGateService::class);

            // Check if user exists in FortiGate
            $existsInFortiGate = $fortiGateService->userExists($this->fortigate_username);

            if ($this->shouldBeInFortiGate()) {
                if ($existsInFortiGate) {
                    // Update existing user - DON'T send password (FortiGate rejects it with 403)
                    $updateData = [
                        'email-to' => $this->email,
                    ];

                    // Only add expiry date if user has one
                    if ($this->expires_at) {
                        $updateData['expiry-date'] = $this->expires_at->format('Y-m-d');
                    }

                    $result = $fortiGateService->updateUser($this->fortigate_username, $updateData);
                } else {
                    // Create new user - password required
                    $createData = [
                        'username' => $this->fortigate_username,
                        'password' => $this->fortigate_password ?? $this->generateSecurePassword(),
                        'email' => $this->email,
                        'status' => $this->status === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    ];

                    if ($this->expires_at) {
                        $createData['expires_at'] = $this->expires_at->format('Y-m-d');
                    }

                    $result = $fortiGateService->createUser($createData);
                }
                
                // Enable or disable based on status
                if ($this->isActive()) {
                    $fortiGateService->enableUser($this->fortigate_username);
                } else {
                    $fortiGateService->disableUser($this->fortigate_username);
                }
                
                $this->updateFortiGateSync(User::SYNC_SYNCED);
                
                Log::info('User synced with FortiGate', [
                    'user_id' => $this->id,
                    'fortigate_username' => $this->fortigate_username,
                    'action' => $existsInFortiGate ? 'updated' : 'created',
                ]);
                
                return true;
                
            } else {
                // User should not be in FortiGate (deleted, expired, etc.)
                if ($existsInFortiGate) {
                    // Delete from FortiGate
                    $fortiGateService->deleteUser($this->fortigate_username);

                    Log::info('User removed from FortiGate', [
                        'user_id' => $this->id,
                        'fortigate_username' => $this->fortigate_username,
                        'reason' => $this->isExpired() ? 'expired' : 'status_' . $this->status,
                    ]);

                    // If user is expired, soft delete from application
                    if ($this->isExpired() && $this->status !== User::STATUS_DELETED) {
                        $this->status = User::STATUS_DELETED;
                        $this->save();

                        Log::info('Expired user deleted from application', [
                            'user_id' => $this->id,
                            'fortigate_username' => $this->fortigate_username,
                            'expired_at' => $this->expires_at,
                        ]);
                    }
                }

                $this->updateFortiGateSync(User::SYNC_NOT_REQUIRED);
                return true;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to sync user with FortiGate', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->updateFortiGateSync(User::SYNC_ERROR, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user should exist in FortiGate
     */
    public function shouldBeInFortiGate(): bool
    {
        // User should be in FortiGate if:
        // 1. They are active
        // 2. They have accepted the charter (or are employees/consultants who don't need to)
        // 3. They are validated (for guests)
        // 4. They are not expired

        if (!$this->is_active || $this->status === User::STATUS_DELETED) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->isGuest() && !$this->validated_at) {
            return false;
        }

        // Only guests need charter acceptance before FortiGate creation
        // Employees and consultants are created by admins and don't need charter
        if ($this->isGuest() && !$this->hasAcceptedCharter()) {
            return false;
        }

        return true;
    }
    
    /**
     * Generate secure password
     */
    protected function generateSecurePassword(): string
    {
        $length = app(FortiGateService::class)->settings->default_password_length ?? 12;
        
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one of each type
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
    
    /**
     * Remove user from FortiGate
     */
    public function removeFromFortiGate(): bool
    {
        try {
            $fortiGateService = app(FortiGateService::class);
            
            if ($fortiGateService->userExists($this->fortigate_username)) {
                // Terminate any active sessions
                $fortiGateService->terminateSession($this->fortigate_username);
                
                // Delete the user
                $fortiGateService->deleteUser($this->fortigate_username);
                
                Log::info('User removed from FortiGate', [
                    'user_id' => $this->id,
                    'fortigate_username' => $this->fortigate_username,
                ]);
            }
            
            $this->updateFortiGateSync(User::SYNC_NOT_REQUIRED);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove user from FortiGate', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->updateFortiGateSync(User::SYNC_ERROR, $e->getMessage());
            return false;
        }
    }
}