<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\DeleteUnvalidatedGuestJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestUserService
{
    protected $fortiGateService;
    protected $portalDataService;

    public function __construct(
        FortiGateService $fortiGateService,
        PortalDataService $portalDataService
    ) {
        $this->fortiGateService = $fortiGateService;
        $this->portalDataService = $portalDataService;
    }

    /**
     * Create a guest user with optional portal data
     *
     * @param array $userData User registration data
     * @param array|null $portalData Optional portal data for auto-authentication
     * @return User
     */
    public function createGuestWithPortalData(array $userData, ?array $portalData = null): User
    {
        // Generate a secure password
        $password = $this->generateSecurePassword();
        
        // Create the user
        $user = new User();
        $user->name = $userData['first_name'] . ' ' . $userData['last_name'];
        $user->first_name = $userData['first_name'];
        $user->last_name = $userData['last_name'];
        $user->email = $userData['email'];
        $user->password = Hash::make($password);
        $user->temp_password = $password; // Store temporarily for email
        $user->user_type = User::TYPE_GUEST;
        $user->status = User::STATUS_PENDING;
        $user->is_active = false;
        
        // Optional fields
        $user->phone = $userData['phone'] ?? null;
        $user->company_name = $userData['company_name'] ?? null;
        $user->visit_reason = $userData['visit_reason'] ?? null;
        
        // Set validation token and expiry (30 minutes)
        $user->validation_token = Str::random(64);
        $user->validation_expires_at = now()->addMinutes(30);
        
        // Set guest expiry (24 hours from validation)
        $user->expires_at = now()->addHours(24);
        
        // FortiGate username (use email prefix)
        $emailPrefix = explode('@', $user->email)[0];
        $user->fortigate_username = 'guest_' . $emailPrefix . '_' . Str::random(4);
        
        // Store portal data if provided
        if ($portalData) {
            $user->portal_data = json_encode($this->portalDataService->sanitizePortalData($portalData));
            
            // Store additional network info if available
            if (isset($portalData['client_ip'])) {
                $user->registration_ip = $portalData['client_ip'];
            }
            if (isset($portalData['ssid'])) {
                $user->network_ssid = $portalData['ssid'];
            }
        }
        
        $user->save();
        
        // Create user in FortiGate (but disabled until email validation)
        try {
            if ($this->fortiGateService->isConfigured()) {
                $this->createFortiGateUser($user, $password);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create user in FortiGate during registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the registration if FortiGate sync fails
            $user->fortigate_sync_status = User::SYNC_ERROR;
            $user->fortigate_sync_error = $e->getMessage();
            $user->save();
        }
        
        // Schedule deletion job if not validated within 30 minutes
        DeleteUnvalidatedGuestJob::dispatch($user->id)->delay(now()->addMinutes(30));
        
        return $user;
    }

    /**
     * Generate a secure password for guest users
     *
     * @return string
     */
    protected function generateSecurePassword(): string
    {
        // Generate a password that meets ANSSI requirements
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
        
        // Fill the rest randomly (total 12 characters)
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Create user in FortiGate
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    protected function createFortiGateUser(User $user, string $password): void
    {
        $settings = \App\Models\FortiGateSettings::current();
        
        $userData = [
            'name' => $user->fortigate_username,
            'status' => 'disable', // Disabled until email validation
            'passwd' => $password,
            'email' => $user->email,
            'comments' => 'Guest user - ' . $user->name . ' (' . $user->company_name . ')',
        ];
        
        // Add to configured group
        if (!empty($settings->user_group)) {
            $userData['groups'] = [
                ['name' => $settings->user_group]
            ];
        }
        
        $this->fortiGateService->createUser($userData);
        
        $user->fortigate_sync_status = User::SYNC_SYNCED;
        $user->fortigate_synced_at = now();
        $user->save();
    }

    /**
     * Get authentication URL for guest with portal data
     *
     * @param User $user
     * @return string|null
     */
    public function getAuthenticationUrl(User $user): ?string
    {
        if (empty($user->portal_data)) {
            return null;
        }
        
        try {
            $portalData = json_decode($user->portal_data, true);
            if (!$portalData) {
                return null;
            }
            
            // Use the FortiGate username and temporary password
            $username = $user->fortigate_username;
            $password = $user->temp_password;
            
            if (!$username || !$password) {
                Log::warning('Missing credentials for auto-authentication', [
                    'user_id' => $user->id
                ]);
                return null;
            }
            
            return $this->portalDataService->generateAuthUrl($portalData, $username, $password);
        } catch (\Exception $e) {
            Log::error('Failed to generate authentication URL', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if user has portal data for auto-authentication
     *
     * @param User $user
     * @return bool
     */
    public function hasPortalData(User $user): bool
    {
        if (empty($user->portal_data)) {
            return false;
        }
        
        try {
            $portalData = json_decode($user->portal_data, true);
            return $this->portalDataService->hasAutoAuth($portalData);
        } catch (\Exception $e) {
            return false;
        }
    }
}