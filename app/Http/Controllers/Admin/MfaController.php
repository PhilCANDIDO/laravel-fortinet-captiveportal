<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MfaService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    protected MfaService $mfaService;
    protected AuditService $auditService;
    
    public function __construct(MfaService $mfaService, AuditService $auditService)
    {
        $this->mfaService = $mfaService;
        $this->auditService = $auditService;
    }
    
    public function showSetupForm()
    {
        $user = Auth::guard('admin')->user();
        
        if ($user->google2fa_enabled) {
            return redirect()->route('admin.dashboard');
        }
        
        $secret = $this->mfaService->generateSecret();
        session(['mfa_secret' => $secret]);
        
        $qrCode = $this->mfaService->generateQrCode($user, $secret);
        
        return view('admin.mfa.setup', compact('qrCode', 'secret'));
    }
    
    public function enableMfa(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);
        
        $user = Auth::guard('admin')->user();
        $secret = session('mfa_secret');
        
        if (!$secret) {
            return redirect()->route('admin.mfa.setup')
                ->with('error', 'Session expired. Please start the setup again.');
        }
        
        if (!$this->mfaService->verify($secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid.'],
            ]);
        }
        
        $backupCodes = $this->mfaService->enableFor($user, $secret);
        
        session()->forget('mfa_secret');
        session(['mfa_verified' => true]);
        
        $this->auditService->logMfaEnabled($user->email);
        
        return view('admin.mfa.backup-codes', compact('backupCodes'));
    }
    
    public function showVerifyForm()
    {
        $user = Auth::guard('admin')->user();
        
        if (!$user->google2fa_enabled) {
            return redirect()->route('admin.dashboard');
        }
        
        if (session('mfa_verified')) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.mfa.verify');
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $user = Auth::guard('admin')->user();
        
        $isValid = false;
        $method = 'totp';
        
        if (strlen($request->code) === 6 && ctype_digit($request->code)) {
            $isValid = $this->mfaService->verifyForUser($user, $request->code);
        } else {
            $isValid = $this->mfaService->verifyBackupCode($user, $request->code);
            $method = 'backup_code';
        }
        
        if (!$isValid) {
            $this->auditService->logMfaFailed($user->email, $method);
            
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid.'],
            ]);
        }
        
        session(['mfa_verified' => true]);
        
        $this->auditService->logMfaVerified($user->email, $method);
        
        if ($method === 'backup_code' && !$user->hasRemainingBackupCodes()) {
            return redirect()->route('admin.mfa.regenerate-codes')
                ->with('warning', 'You have used all your backup codes. Please generate new ones.');
        }
        
        return redirect()->intended(route('admin.dashboard'));
    }
    
    public function showRegenerateCodesForm()
    {
        return view('admin.mfa.regenerate-codes');
    }
    
    public function regenerateCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password:admin',
        ]);
        
        $user = Auth::guard('admin')->user();
        
        $backupCodes = $this->mfaService->regenerateBackupCodes($user);
        
        $this->auditService->logMfaEnabled($user->email);
        
        return view('admin.mfa.backup-codes', compact('backupCodes'));
    }
    
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password:admin',
        ]);
        
        $user = Auth::guard('admin')->user();
        
        $this->mfaService->disableFor($user);
        
        session()->forget('mfa_verified');
        
        $this->auditService->logMfaDisabled($user->email);
        
        return redirect()->route('admin.dashboard')
            ->with('success', 'Two-factor authentication has been disabled.');
    }
}