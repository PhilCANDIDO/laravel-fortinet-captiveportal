<?php

namespace App\Livewire\Admin\Auth;

use App\Services\AuditService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $showForceLogoutModal = false;
    public $forceLogoutEmail = '';
    public $forceLogoutPassword = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];
    
    public function login(AuditService $auditService)
    {
        $this->validate();
        
        $key = 'admin-login:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");
            return;
        }
        
        if (Auth::guard('admin')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($key);
            
            $user = Auth::guard('admin')->user();
            
            if ($user->isAccountLocked()) {
                Auth::guard('admin')->logout();
                $auditService->logFailedLogin($this->email, 'Account locked');
                $this->addError('email', 'Your account has been locked. Please contact an administrator.');
                return;
            }
            
            if (!$user->is_active) {
                Auth::guard('admin')->logout();
                $auditService->logFailedLogin($this->email, 'Account inactive');
                $this->addError('email', 'Your account is inactive. Please contact an administrator.');
                return;
            }
            
            $user->updateLastLogin(request()->ip());

            session()->regenerate();

            $auditService->logLogin($user->email, true);

            // Check if password change required (first login or expired)
            if ($user->is_first_login || $user->must_change_password) {
                session(['must_change_password' => true]);
                return redirect()->route('admin.password.change')
                    ->with('info', __('auth.first_login_password_change'));
            }

            if ($user->google2fa_enabled) {
                return redirect()->route('admin.mfa.verify');
            }

            session(['mfa_verified' => true]);

            return redirect()->intended(route('admin.dashboard'));
        }
        
        RateLimiter::hit($key, 60);
        
        $user = \App\Models\AdminUser::where('email', $this->email)->first();
        
        if ($user) {
            $user->incrementFailedLoginAttempts();
            
            if ($user->isAccountLocked()) {
                $auditService->logAccountLocked($this->email, $user->failed_login_attempts);
            }
        }
        
        $auditService->logFailedLogin($this->email, 'Invalid credentials');
        
        $this->addError('email', 'The provided credentials do not match our records.');
    }

    public function openForceLogoutModal()
    {
        $this->showForceLogoutModal = true;
    }

    public function forceLogout(AuditService $auditService)
    {
        $this->validate([
            'forceLogoutEmail' => 'required|email',
            'forceLogoutPassword' => 'required',
        ]);

        if (Auth::guard('admin')->attempt(['email' => $this->forceLogoutEmail, 'password' => $this->forceLogoutPassword], false)) {
            $user = Auth::guard('admin')->user();

            \App\Models\AdminSession::where('admin_user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Auth::guard('admin')->logout();

            $auditService->log(
                'force_logout',
                "Admin forced logout of all sessions: {$user->email}",
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                ]
            );

            session()->flash('success', __('auth.all_sessions_logged_out'));

            $this->showForceLogoutModal = false;
            $this->forceLogoutEmail = '';
            $this->forceLogoutPassword = '';
        } else {
            $this->addError('forceLogoutEmail', __('auth.invalid_credentials'));
        }
    }

    public function render()
    {
        return view('livewire.admin.auth.login')
            ->extends('layouts.guest')
            ->section('content');
    }
}