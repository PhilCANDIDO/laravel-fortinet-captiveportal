<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\FortiGateService;
use App\Services\PortalDataService;
use App\Services\GuestUserService;
use App\Http\Requests\GuestRegistrationRequest;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestRegistrationController extends Controller
{
    protected $userService;
    protected $notificationService;
    protected $fortiGateService;
    protected $portalDataService;
    protected $guestUserService;
    
    public function __construct(
        UserService $userService,
        NotificationService $notificationService,
        FortiGateService $fortiGateService,
        PortalDataService $portalDataService,
        GuestUserService $guestUserService
    ) {
        $this->userService = $userService;
        $this->notificationService = $notificationService;
        $this->fortiGateService = $fortiGateService;
        $this->portalDataService = $portalDataService;
        $this->guestUserService = $guestUserService;
    }
    
    /**
     * Show guest registration form
     */
    public function showForm(Request $request)
    {
        // Check if portal_data is provided in the query parameters
        $portalData = null;
        $portalInfo = null;
        
        if ($request->has('portal_data')) {
            $encodedData = $request->query('portal_data');
            $portalData = $this->portalDataService->decodePortalData($encodedData);
            
            if ($portalData) {
                // Store in session for later use
                $this->portalDataService->storeInSession($portalData);
                $portalInfo = $this->portalDataService->getPortalInfo($portalData);
                
                Log::info('Guest registration form loaded with portal data', [
                    'ssid' => $portalInfo['ssid'] ?? 'unknown',
                    'client_ip' => $portalInfo['client_ip'] ?? 'unknown'
                ]);
            } else {
                Log::warning('Invalid portal data provided', [
                    'encoded_data' => substr($encodedData, 0, 100) . '...'
                ]);
            }
        }
        
        return view('guest.register', compact('portalInfo'));
    }
    
    /**
     * Handle guest registration
     */
    public function register(GuestRegistrationRequest $request)
    {
        // Get portal data from session or request
        $portalData = null;
        if ($request->has('portal_data')) {
            $portalData = $request->getPortalData();
        } else {
            // Try to get from session
            $portalData = $this->portalDataService->getFromSession();
        }
        
        DB::beginTransaction();
        
        try {
            // Create guest user with portal data
            $user = $this->guestUserService->createGuestWithPortalData(
                $request->validated(),
                $portalData
            );
            
            // Get the temporary password for the email
            $password = $user->temp_password;
            
            // Only send validation email if email validation is enabled
            $emailValidationEnabled = \App\Models\Setting::isGuestEmailValidationEnabled();
            if ($emailValidationEnabled) {
                // Send validation email
                $this->notificationService->sendGuestValidationEmail($user, $password);
            }
            
            // Log the registration
            AuditLog::create([
                'event_type' => AuditLog::EVENT_TYPES['USER_CREATED'],
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'guest',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'action' => 'guest.registered',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'metadata' => [
                    'name' => $user->name,
                    'company' => $user->company_name,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
            ]);
            
            DB::commit();
            
            // Store portal data in session for success page
            if ($portalData) {
                $this->portalDataService->storeInSession($portalData);
            }
            
            return redirect()->route('guest.register.success')
                ->with('email', $user->email)
                ->with('password', $password)
                ->with('username', $user->fortigate_username)
                ->with('has_portal_data', $portalData !== null)
                ->with('email_validation_enabled', $emailValidationEnabled)
                ->with('user_active', $user->is_active);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Guest registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except('password'),
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => __('messages.registration_failed')])
                ->withInput();
        }
    }
    
    /**
     * Show registration success page
     */
    public function success(Request $request)
    {
        $email = $request->session()->get('email');
        $password = $request->session()->get('password');
        $username = $request->session()->get('username');
        $hasPortalData = $request->session()->get('has_portal_data', false);
        $emailValidationEnabled = $request->session()->get('email_validation_enabled', true);
        $userActive = $request->session()->get('user_active', false);
        
        if (!$email) {
            return redirect()->route('guest.register');
        }
        
        // Check for portal data to generate auto-auth URL
        $autoAuthUrl = null;
        $portalInfo = null;
        $portalData = $this->portalDataService->getFromSession();
        
        // Generate auto-auth URL if:
        // 1. Portal data is available AND
        // 2. Either email validation is disabled OR user is already active
        if ($portalData && $hasPortalData && (!$emailValidationEnabled || $userActive)) {
            try {
                // Generate authentication URL with credentials
                $autoAuthUrl = $this->portalDataService->generateAuthUrl(
                    $portalData,
                    $username,
                    $password
                );
                
                $portalInfo = $this->portalDataService->getPortalInfo($portalData);
                
                Log::info('Auto-authentication URL generated for guest', [
                    'email' => $email,
                    'ssid' => $portalInfo['ssid'] ?? 'unknown',
                    'email_validation_enabled' => $emailValidationEnabled
                ]);
                
                // Clear portal data from session after use
                $this->portalDataService->clearFromSession();
            } catch (\Exception $e) {
                Log::error('Failed to generate auto-authentication URL', [
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Fallback to manual captive portal URL from settings
        $captivePortalUrl = null;
        if (!$autoAuthUrl) {
            try {
                $settings = \App\Models\FortiGateSettings::current();
                $captivePortalUrl = $settings->captive_portal_url ?? null;
            } catch (\Exception $e) {
                Log::warning('Could not retrieve FortiGate settings', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return view('guest.success', compact(
            'email',
            'password',
            'username',
            'captivePortalUrl',
            'autoAuthUrl',
            'portalInfo',
            'emailValidationEnabled',
            'userActive'
        ));
    }
    
    /**
     * Validate guest email
     */
    public function validateEmail(Request $request, $token)
    {
        if (!$request->hasValidSignature()) {
            return view('guest.validation-failed', [
                'reason' => 'expired',
            ]);
        }
        
        $user = User::where('validation_token', $token)
            ->where('user_type', User::TYPE_GUEST)
            ->where('status', User::STATUS_PENDING)
            ->first();
            
        if (!$user) {
            return view('guest.validation-failed', [
                'reason' => 'invalid',
            ]);
        }
        
        DB::beginTransaction();
        
        try {
            // Mark as validated
            $user->validated_at = now();
            $user->validation_token = null;
            $user->validation_expires_at = null;
            $user->status = User::STATUS_ACTIVE;
            $user->is_active = true;
            $user->save();
            
            // Enable the user in FortiGate (it was created during registration but disabled)
            try {
                if ($this->fortiGateService->isConfigured()) {
                    $username = $user->fortigate_username ?? $user->email;
                    
                    // Update the user to enable status and ensure they're in the group
                    $userData = [
                        'status' => 'enable',
                    ];
                    
                    // Also ensure the user is in the configured group
                    $settings = \App\Models\FortiGateSettings::current();
                    if (!empty($settings->user_group)) {
                        $userData['groups'] = [
                            ['name' => $settings->user_group]
                        ];
                    }
                    
                    $this->fortiGateService->updateUser($username, $userData);
                    $user->fortigate_sync_status = User::SYNC_SYNCED;
                    $user->fortigate_synced_at = now();
                    $user->save();
                }
            } catch (\Exception $e) {
                Log::error('Failed to enable user in FortiGate: ' . $e->getMessage());
                // Don't fail the validation process if FortiGate sync fails
                $user->fortigate_sync_status = User::SYNC_ERROR;
                $user->fortigate_sync_error = $e->getMessage();
                $user->save();
            }
            
            // Log the validation
            AuditLog::create([
                'event_type' => 'email_validated',
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'guest',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'action' => 'guest.validated',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'metadata' => [
                    'name' => $user->name,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
            ]);
            
            DB::commit();
            
            // Check if user has portal data for auto-authentication
            $autoAuthUrl = null;
            $portalInfo = null;
            
            if (!empty($user->portal_data)) {
                try {
                    $portalData = json_decode($user->portal_data, true);
                    if ($portalData && $this->portalDataService->hasAutoAuth($portalData)) {
                        // Generate auth URL if we still have the password
                        // Note: For security, we don't store plain passwords, so auto-auth
                        // might only work during initial registration flow
                        $portalInfo = $this->portalDataService->getPortalInfo($portalData);
                        
                        Log::info('Guest email validated with portal data', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'ssid' => $portalInfo['ssid'] ?? 'unknown'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process portal data during validation', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return view('guest.validation-success', [
                'user' => $user,
                'charterUrl' => route('guest.charter.show'),
                'portalInfo' => $portalInfo,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Guest validation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            
            return view('guest.validation-failed', [
                'reason' => 'error',
            ]);
        }
    }
    
    /**
     * Show charter acceptance page
     */
    public function showCharter()
    {
        $charter = \App\Models\Setting::getCharter();
        return view('guest.charter', compact('charter'));
    }
    
    /**
     * Accept charter
     */
    public function acceptCharter(Request $request)
    {
        $request->validate([
            'accept' => 'required|accepted',
        ]);
        
        // Store charter acceptance in session
        $request->session()->put('charter_accepted', true);
        
        return redirect()->route('guest.portal');
    }
    
    /**
     * Show guest portal
     */
    public function portal()
    {
        // This would typically be handled by the captive portal
        // Shows connection status and remaining time
        return view('guest.portal');
    }
}