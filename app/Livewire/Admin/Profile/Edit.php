<?php

namespace App\Livewire\Admin\Profile;

use App\Models\AdminUser;
use App\Services\AuditService;
use App\Services\PasswordService;
use App\Services\MfaService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Edit extends Component
{
    public $activeTab = 'profile';
    
    // Profile fields
    public $name = '';
    public $email = '';
    
    // Password fields
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    
    // MFA fields
    public $mfaEnabled = false;
    public $mfaQrCode = '';
    public $mfaSecret = '';
    public $mfaBackupCodes = [];
    public $verificationCode = '';
    public $showMfaSetup = false;
    public $showBackupCodes = false;
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
    ];

    public function mount()
    {
        $user = auth()->guard('admin')->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->mfaEnabled = $user->google2fa_enabled;
    }

    public function render()
    {
        return view('livewire.admin.profile.edit');
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);
        
        $user = auth()->guard('admin')->user();
        
        // Check if email is unique (excluding current user)
        $emailExists = AdminUser::where('email', $this->email)
            ->where('id', '!=', $user->id)
            ->exists();
            
        if ($emailExists) {
            $this->addError('email', 'Cet email est déjà utilisé.');
            return;
        }
        
        $changes = [];
        
        if ($user->name !== $this->name) {
            $changes['name'] = ['old' => $user->name, 'new' => $this->name];
        }
        
        if ($user->email !== $this->email) {
            $changes['email'] = ['old' => $user->email, 'new' => $this->email];
        }
        
        if (!empty($changes)) {
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
            
            AuditService::log('profile_updated', 'account', [
                'changes' => $changes,
            ]);
            
            session()->flash('profile_message', 'Profil mis à jour avec succès.');
        } else {
            session()->flash('profile_message', 'Aucune modification détectée.');
        }
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:16|confirmed|different:current_password',
        ]);
        
        $user = auth()->guard('admin')->user();
        
        // Verify current password
        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Le mot de passe actuel est incorrect.');
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
            $this->addError('password', 'Ce mot de passe a déjà été utilisé récemment.');
            return;
        }
        
        // Save current password to history
        $user->savePasswordToHistory();
        
        // Update password
        $user->update([
            'password' => $this->password,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
        ]);
        
        AuditService::log('password_changed', 'security', [
            'admin_id' => $user->id,
        ]);
        
        // Clear password fields
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
        
        session()->flash('password_message', 'Mot de passe mis à jour avec succès.');
    }

    public function initiateMfaSetup()
    {
        $user = auth()->guard('admin')->user();
        
        if ($user->google2fa_enabled) {
            session()->flash('mfa_error', 'L\'authentification à deux facteurs est déjà activée.');
            return;
        }
        
        $mfaService = new MfaService();
        $this->mfaSecret = $mfaService->generateSecret();
        $this->mfaQrCode = $mfaService->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $this->mfaSecret
        );
        
        $this->showMfaSetup = true;
    }

    public function verifyAndEnableMfa()
    {
        $this->validate([
            'verificationCode' => 'required|digits:6',
        ]);
        
        $mfaService = new MfaService();
        
        if (!$mfaService->verifyCode($this->mfaSecret, $this->verificationCode)) {
            $this->addError('verificationCode', 'Le code de vérification est incorrect.');
            return;
        }
        
        $user = auth()->guard('admin')->user();
        
        // Generate backup codes
        $this->mfaBackupCodes = $user->generateBackupCodes();
        
        // Enable MFA
        $user->update([
            'google2fa_secret' => encrypt($this->mfaSecret),
            'google2fa_enabled' => true,
            'google2fa_enabled_at' => now(),
        ]);
        
        AuditService::log('mfa_enabled', 'security', [
            'admin_id' => $user->id,
        ]);
        
        $this->mfaEnabled = true;
        $this->showMfaSetup = false;
        $this->showBackupCodes = true;
        $this->verificationCode = '';
        
        session()->flash('mfa_message', 'Authentification à deux facteurs activée avec succès.');
    }

    public function disableMfa()
    {
        $user = auth()->guard('admin')->user();
        
        if (!$user->google2fa_enabled) {
            session()->flash('mfa_error', 'L\'authentification à deux facteurs n\'est pas activée.');
            return;
        }
        
        $user->resetMfa();
        
        AuditService::log('mfa_disabled', 'security', [
            'admin_id' => $user->id,
        ]);
        
        $this->mfaEnabled = false;
        
        session()->flash('mfa_message', 'Authentification à deux facteurs désactivée.');
    }

    public function regenerateBackupCodes()
    {
        $user = auth()->guard('admin')->user();
        
        if (!$user->google2fa_enabled) {
            session()->flash('mfa_error', 'L\'authentification à deux facteurs doit être activée.');
            return;
        }
        
        $this->mfaBackupCodes = $user->generateBackupCodes();
        $this->showBackupCodes = true;
        
        AuditService::log('mfa_backup_codes_regenerated', 'security', [
            'admin_id' => $user->id,
        ]);
        
        session()->flash('mfa_message', 'Codes de récupération régénérés avec succès.');
    }

    public function downloadBackupCodes()
    {
        $codes = implode("\n", $this->mfaBackupCodes);
        $filename = 'backup-codes-' . date('Y-m-d') . '.txt';
        
        return response()->streamDownload(function () use ($codes) {
            echo "Codes de récupération - " . config('app.name') . "\n";
            echo "Générés le: " . date('Y-m-d H:i:s') . "\n";
            echo "=====================================\n\n";
            echo $codes . "\n\n";
            echo "=====================================\n";
            echo "Conservez ces codes dans un endroit sûr.\n";
            echo "Chaque code ne peut être utilisé qu'une seule fois.\n";
        }, $filename);
    }

    public function cancelMfaSetup()
    {
        $this->showMfaSetup = false;
        $this->mfaSecret = '';
        $this->mfaQrCode = '';
        $this->verificationCode = '';
    }

    public function hideBackupCodes()
    {
        $this->showBackupCodes = false;
        $this->mfaBackupCodes = [];
    }

    public function revokeSession($sessionId)
    {
        $user = auth()->guard('admin')->user();
        $session = $user->sessions()->where('id', $sessionId)->first();
        
        if ($session && $session->id !== session()->getId()) {
            $session->update(['is_active' => false]);
            
            AuditService::log('session_revoked', 'security', [
                'session_id' => $sessionId,
                'admin_id' => $user->id,
            ]);
            
            session()->flash('session_message', 'Session révoquée avec succès.');
        }
    }

    public function revokeAllSessions()
    {
        $user = auth()->guard('admin')->user();
        $currentSessionId = session()->getId();
        
        $user->sessions()
            ->where('id', '!=', $currentSessionId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        
        AuditService::log('all_sessions_revoked', 'security', [
            'admin_id' => $user->id,
        ]);
        
        session()->flash('session_message', 'Toutes les autres sessions ont été révoquées.');
    }
}