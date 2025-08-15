<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\FortiGateService;
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
    
    public function __construct(
        UserService $userService,
        NotificationService $notificationService,
        FortiGateService $fortiGateService
    ) {
        $this->userService = $userService;
        $this->notificationService = $notificationService;
        $this->fortiGateService = $fortiGateService;
    }
    
    /**
     * Show guest registration form
     */
    public function showForm()
    {
        return view('guest.register');
    }
    
    /**
     * Handle guest registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:200',
            'visit_reason' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        DB::beginTransaction();
        
        try {
            // Create guest user (password is generated inside this method)
            $user = $this->userService->createGuest($request->all());
            
            // Get the temporary password for the email
            $password = $user->temp_password;
            
            // Send validation email
            $this->notificationService->sendGuestValidationEmail($user, $password);
            
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
            
            return redirect()->route('guest.register.success')
                ->with('email', $user->email)
                ->with('password', $password)
                ->with('username', $user->email);
                
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
        
        if (!$email) {
            return redirect()->route('guest.register');
        }
        
        // Get captive portal URL from FortiGate settings
        $captivePortalUrl = \App\Models\FortiGateSettings::current()->captive_portal_url;
        
        return view('guest.success', compact('email', 'password', 'username', 'captivePortalUrl'));
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
                    
                    // Update the user to enable status
                    $userData = [
                        'status' => 'enable',
                    ];
                    
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
            
            return view('guest.validation-success', [
                'user' => $user,
                'charterUrl' => route('guest.charter.show'),
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