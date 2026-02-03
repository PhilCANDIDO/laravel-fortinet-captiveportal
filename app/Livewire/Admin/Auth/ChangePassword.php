<?php

namespace App\Livewire\Admin\Auth;

use App\Services\AuditService;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
class ChangePassword extends Component
{
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    public $passwordStrength = 0;
    public $passwordFeedback = '';
    
    protected $rules = [
        'current_password' => 'required',
        'password' => 'required|min:16|confirmed|different:current_password',
    ];

    public function render()
    {
        return view('livewire.admin.auth.change-password')
            ->extends('layouts.guest')
            ->section('content');
    }

    public function updatedPassword()
    {
        if (empty($this->password)) {
            $this->passwordStrength = 0;
            $this->passwordFeedback = '';
            return;
        }
        
        $passwordService = new PasswordService();
        $strength = $passwordService->checkPasswordStrength($this->password);
        $this->passwordStrength = $strength['score'];
        $this->passwordFeedback = $strength['feedback'];
    }

    public function changePassword()
    {
        $this->validate();
        
        $user = auth()->guard('admin')->user();
        
        // Verify current password
        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', __('auth.current_password_incorrect'));
            return;
        }
        
        // Validate new password with PasswordService
        $passwordService = new PasswordService();
        $validation = $passwordService->validatePassword($this->password);
        
        if (!$validation['valid']) {
            $this->addError('password', implode(' ', $validation['errors']));
            return;
        }
        
        // Check password history
        if ($user->hasUsedPassword($this->password)) {
            $this->addError('password', __('auth.password_recently_used'));
            return;
        }
        
        // Save current password to history
        $user->savePasswordToHistory();
        
        // Update password
        $user->update([
            'password' => $this->password,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
            'must_change_password' => false,
            'is_first_login' => false,
        ]);
        
        AuditService::log('password_changed', 'security', [
            'admin_id' => $user->id,
            'reason' => session('must_change_password') ? 'first_login' : 'user_initiated',
        ]);
        
        // Clear the must change password flag
        session()->forget('must_change_password');
        
        // Continue to MFA or dashboard
        if ($user->google2fa_enabled) {
            return redirect()->route('admin.mfa.verify');
        }
        
        session(['mfa_verified' => true]);
        
        return redirect()->route('admin.dashboard')
            ->with('success', __('auth.password_changed_successfully'));
    }
}