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
        // Check for duplicate email and handle expired guests
        $existingUser = User::where('email', $userData['email'])
            ->where('user_type', User::TYPE_GUEST)
            ->first();

        if ($existingUser) {
            // If the existing guest has expired, delete it and allow re-registration
            if ($existingUser->isExpired() || $existingUser->status === User::STATUS_EXPIRED) {
                Log::info('Auto-cleanup: Deleting expired guest to allow re-registration', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email,
                    'expired_at' => $existingUser->expires_at,
                ]);

                // Delete the expired guest (includes FortiGate removal)
                $existingUser->removeFromFortiGate();
                $existingUser->delete();

                // Continue with new registration below
            } else {
                // Guest is still active or pending validation
                $remainingTime = $existingUser->expires_at ? $existingUser->expires_at->diffForHumans() : __('common.unknown');
                throw new \Exception(__('messages.guest_email_already_exists', ['time' => $remainingTime]));
            }
        }

        // Generate a secure password
        $password = $this->generateSecurePassword();

        // Check if email validation is enabled
        $emailValidationEnabled = \App\Models\Setting::isGuestEmailValidationEnabled();

        // Create the user
        $user = new User();
        $user->name = $userData['first_name'] . ' ' . $userData['last_name'];
        $user->first_name = $userData['first_name'];
        $user->last_name = $userData['last_name'];
        $user->email = $userData['email'];
        $user->password = Hash::make($password);
        $user->temp_password = $password; // Store temporarily for email
        $user->user_type = User::TYPE_GUEST;
        
        // Optional fields
        $user->phone = $userData['phone'] ?? null;
        $user->company_name = $userData['company_name'] ?? null;
        $user->visit_reason = $userData['visit_reason'] ?? null;
        
        // Set guest expiry (24 hours from now)
        $user->expires_at = now()->addHours(24);
        
        // Set status based on email validation setting
        if ($emailValidationEnabled) {
            $user->status = User::STATUS_PENDING;
            $user->is_active = false;
            // Set validation token and expiry (30 minutes)
            $user->validation_token = Str::random(64);
            $user->validation_expires_at = now()->addMinutes(30);
            $user->validated_at = null;
        } else {
            // If email validation is disabled, activate immediately
            $user->status = User::STATUS_ACTIVE;
            $user->is_active = true;
            $user->validated_at = now();
            // Don't set validation token when validation is disabled
            $user->validation_token = null;
            $user->validation_expires_at = null;
        }
        
        // Save first to get the ID
        $user->save();
        
        // Generate FortiGate username using the pattern guest-{{id}}
        $user->fortigate_username = 'guest-' . $user->id;
        
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
        
        // Save the updated user with fortigate_username
        $user->save();
        
        // Create user in FortiGate
        try {
            if ($this->fortiGateService->isConfigured()) {
                // If email validation is enabled, create as disabled; otherwise, create as enabled
                $this->createFortiGateUser($user, $password, !$emailValidationEnabled);
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
        
        // Only schedule deletion job if email validation is enabled
        if ($emailValidationEnabled) {
            DeleteUnvalidatedGuestJob::dispatch($user->id)->delay(now()->addMinutes(30));
        }
        
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
     * @param bool $enableImmediately
     * @return void
     */
    protected function createFortiGateUser(User $user, string $password, bool $enableImmediately = false): void
    {
        $settings = \App\Models\FortiGateSettings::current();
        
        $userData = [
            'username' => $user->fortigate_username,  // FortiGateService expects 'username' not 'name'
            'status' => $enableImmediately ? 'enable' : 'disable',
            'password' => $password,  // FortiGateService expects 'password' not 'passwd'
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