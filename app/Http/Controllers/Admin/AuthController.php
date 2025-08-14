<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSession;
use App\Services\AuditService;
use App\Services\PasswordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuditService $auditService;
    protected PasswordService $passwordService;
    
    public function __construct(AuditService $auditService, PasswordService $passwordService)
    {
        $this->auditService = $auditService;
        $this->passwordService = $passwordService;
    }
    
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $key = 'admin-login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            $this->auditService->logFailedLogin($request->email, 'Rate limit exceeded');
            
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }
        
        $credentials = $request->only('email', 'password');
        
        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            
            $user = Auth::guard('admin')->user();
            
            if ($user->isAccountLocked()) {
                Auth::guard('admin')->logout();
                
                $this->auditService->logFailedLogin($request->email, 'Account locked');
                
                throw ValidationException::withMessages([
                    'email' => ['Your account has been locked. Please contact an administrator.'],
                ]);
            }
            
            if (!$user->is_active) {
                Auth::guard('admin')->logout();
                
                $this->auditService->logFailedLogin($request->email, 'Account inactive');
                
                throw ValidationException::withMessages([
                    'email' => ['Your account is inactive. Please contact an administrator.'],
                ]);
            }
            
            $user->updateLastLogin($request->ip());
            
            // Invalidate all other active sessions for this user (prevent concurrent sessions)
            AdminSession::where('admin_user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Regenerate session first to get the new ID
            $request->session()->regenerate();
            
            // Create new session record with the regenerated session ID
            AdminSession::create([
                'id' => session()->getId(),
                'admin_user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => '',
                'last_activity' => Carbon::now()->timestamp,
                'is_active' => true,
            ]);
            
            $this->auditService->logLogin($user->email, true);
            
            // Check if it's first login or password must be changed
            if ($user->is_first_login || $user->must_change_password) {
                session(['must_change_password' => true]);
                return redirect()->route('admin.password.change')
                    ->with('info', __('auth.first_login_password_change'));
            }
            
            // Mark first login as complete
            if ($user->is_first_login) {
                $user->update(['is_first_login' => false]);
            }
            
            if ($user->google2fa_enabled) {
                return redirect()->route('admin.mfa.verify');
            }
            
            session(['mfa_verified' => true]);
            
            return redirect()->intended(route('admin.dashboard'));
        }
        
        RateLimiter::hit($key, 60);
        
        $user = \App\Models\AdminUser::where('email', $request->email)->first();
        
        if ($user) {
            $user->incrementFailedLoginAttempts();
            
            if ($user->isAccountLocked()) {
                $this->auditService->logAccountLocked($request->email, $user->failed_login_attempts);
            }
        }
        
        $this->auditService->logFailedLogin($request->email, 'Invalid credentials');
        
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }
    
    public function logout(Request $request)
    {
        $user = Auth::guard('admin')->user();
        
        // Log the logout
        $this->auditService->logLogout();
        
        // Invalidate the admin session
        if ($user) {
            AdminSession::where('admin_user_id', $user->id)
                ->where('id', session()->getId())
                ->update(['is_active' => false]);
        }
        
        Auth::guard('admin')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }
    
    public function extendSession(Request $request)
    {
        // Touch the session to extend it
        $request->session()->regenerate();
        
        // Update last activity for custom session tracking
        if ($user = Auth::guard('admin')->user()) {
            AdminSession::where('admin_user_id', $user->id)
                ->where('id', session()->getId())
                ->update(['last_activity' => Carbon::now()->timestamp]);
        }
        
        return response()->json(['success' => true]);
    }
}