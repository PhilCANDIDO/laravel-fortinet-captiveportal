<?php

namespace App\Livewire\Admin\Mfa;

use App\Services\MfaService;
use App\Services\AuditService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Verify extends Component
{
    public $code = '';
    
    protected $rules = [
        'code' => 'required|string',
    ];
    
    public function verify(MfaService $mfaService, AuditService $auditService)
    {
        $this->validate();
        
        $user = Auth::guard('admin')->user();
        
        if (!$user || !$user->google2fa_enabled) {
            return redirect()->route('admin.dashboard');
        }
        
        $isValid = false;
        $method = 'totp';
        
        if (strlen($this->code) === 6 && ctype_digit($this->code)) {
            $isValid = $mfaService->verifyForUser($user, $this->code);
        } else {
            $isValid = $mfaService->verifyBackupCode($user, $this->code);
            $method = 'backup_code';
        }
        
        if (!$isValid) {
            $auditService->logMfaFailed($user->email, $method);
            $this->addError('code', 'The verification code is invalid.');
            return;
        }
        
        session(['mfa_verified' => true]);
        
        $auditService->logMfaVerified($user->email, $method);
        
        if ($method === 'backup_code' && !$user->hasRemainingBackupCodes()) {
            session()->flash('warning', 'You have used all your backup codes. Please generate new ones.');
            return redirect()->route('admin.mfa.regenerate-codes');
        }
        
        return redirect()->intended(route('admin.dashboard'));
    }
    
    public function render()
    {
        return view('livewire.admin.mfa.verify')
            ->extends('layouts.guest')
            ->section('content');
    }
}