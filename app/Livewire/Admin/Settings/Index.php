<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Models\AuditLog;
use App\Services\AuditService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    // General settings
    public $site_name = '';
    public $site_description = '';
    public $contact_email = '';
    
    // Security settings
    public $audit_retention_days = 90;
    public $session_timeout_minutes = 15;
    public $max_login_attempts = 5;
    public $lockout_duration_minutes = 30;
    public $password_expiry_days = 90;
    public $mfa_grace_period_days = 7;
    
    // Charter settings (multilingual)
    public $charter_text_fr = '';
    public $charter_text_en = '';
    public $charter_text_it = '';
    public $charter_text_es = '';
    
    // Email settings
    public $email_from_name = '';
    public $email_from_address = '';
    
    // Guest settings
    public $guest_access_duration_hours = 24;
    public $guest_validation_timeout_minutes = 30;
    
    // Consultant settings
    public $consultant_max_duration_days = 365;
    
    public $activeTab = 'general';
    
    protected $rules = [
        'site_name' => 'required|string|max:255',
        'site_description' => 'nullable|string|max:500',
        'contact_email' => 'required|email',
        'audit_retention_days' => 'required|integer|min:30|max:3650',
        'session_timeout_minutes' => 'required|integer|min:5|max:120',
        'max_login_attempts' => 'required|integer|min:3|max:10',
        'lockout_duration_minutes' => 'required|integer|min:5|max:1440',
        'password_expiry_days' => 'required|integer|min:30|max:365',
        'mfa_grace_period_days' => 'required|integer|min:0|max:30',
        'charter_text_fr' => 'required|string',
        'charter_text_en' => 'required|string',
        'charter_text_it' => 'required|string',
        'charter_text_es' => 'required|string',
        'email_from_name' => 'required|string|max:255',
        'email_from_address' => 'required|email',
        'guest_access_duration_hours' => 'required|integer|min:1|max:168',
        'guest_validation_timeout_minutes' => 'required|integer|min:5|max:120',
        'consultant_max_duration_days' => 'required|integer|min:1|max:730',
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }

    private function loadSettings()
    {
        $this->site_name = Setting::get('site_name', config('app.name'));
        $this->site_description = Setting::get('site_description', 'Portail captif pour l\'accès au réseau WiFi');
        $this->contact_email = Setting::get('contact_email', 'admin@example.com');
        
        $this->audit_retention_days = Setting::get('audit_retention_days', 90);
        $this->session_timeout_minutes = Setting::get('session_timeout_minutes', 15);
        $this->max_login_attempts = Setting::get('max_login_attempts', 5);
        $this->lockout_duration_minutes = Setting::get('lockout_duration_minutes', 30);
        $this->password_expiry_days = Setting::get('password_expiry_days', 90);
        $this->mfa_grace_period_days = Setting::get('mfa_grace_period_days', 7);
        
        $this->charter_text_fr = Setting::get('charter_text_fr', $this->getDefaultCharter('fr'));
        $this->charter_text_en = Setting::get('charter_text_en', $this->getDefaultCharter('en'));
        $this->charter_text_it = Setting::get('charter_text_it', $this->getDefaultCharter('it'));
        $this->charter_text_es = Setting::get('charter_text_es', $this->getDefaultCharter('es'));
        
        $this->email_from_name = Setting::get('email_from_name', config('mail.from.name'));
        $this->email_from_address = Setting::get('email_from_address', config('mail.from.address'));
        
        $this->guest_access_duration_hours = Setting::get('guest_access_duration_hours', 24);
        $this->guest_validation_timeout_minutes = Setting::get('guest_validation_timeout_minutes', 30);
        
        $this->consultant_max_duration_days = Setting::get('consultant_max_duration_days', 365);
    }

    public function saveGeneralSettings()
    {
        $this->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'contact_email' => 'required|email',
        ]);
        
        Setting::set('site_name', $this->site_name);
        Setting::set('site_description', $this->site_description);
        Setting::set('contact_email', $this->contact_email);
        
        AuditService::log('settings_updated', 'system', [
            'section' => 'general',
            'changes' => [
                'site_name' => $this->site_name,
                'site_description' => $this->site_description,
                'contact_email' => $this->contact_email,
            ],
        ]);
        
        session()->flash('message', 'Paramètres généraux mis à jour avec succès.');
    }

    public function saveSecuritySettings()
    {
        $this->validate([
            'audit_retention_days' => 'required|integer|min:30|max:3650',
            'session_timeout_minutes' => 'required|integer|min:5|max:120',
            'max_login_attempts' => 'required|integer|min:3|max:10',
            'lockout_duration_minutes' => 'required|integer|min:5|max:1440',
            'password_expiry_days' => 'required|integer|min:30|max:365',
            'mfa_grace_period_days' => 'required|integer|min:0|max:30',
        ]);
        
        Setting::set('audit_retention_days', $this->audit_retention_days);
        Setting::set('session_timeout_minutes', $this->session_timeout_minutes);
        Setting::set('max_login_attempts', $this->max_login_attempts);
        Setting::set('lockout_duration_minutes', $this->lockout_duration_minutes);
        Setting::set('password_expiry_days', $this->password_expiry_days);
        Setting::set('mfa_grace_period_days', $this->mfa_grace_period_days);
        
        AuditService::log('settings_updated', 'security', [
            'section' => 'security',
            'changes' => [
                'audit_retention_days' => $this->audit_retention_days,
                'session_timeout_minutes' => $this->session_timeout_minutes,
                'max_login_attempts' => $this->max_login_attempts,
                'lockout_duration_minutes' => $this->lockout_duration_minutes,
                'password_expiry_days' => $this->password_expiry_days,
                'mfa_grace_period_days' => $this->mfa_grace_period_days,
            ],
        ]);
        
        session()->flash('message', 'Paramètres de sécurité mis à jour avec succès.');
    }

    public function saveCharterSettings()
    {
        $this->validate([
            'charter_text_fr' => 'required|string',
            'charter_text_en' => 'required|string',
            'charter_text_it' => 'required|string',
            'charter_text_es' => 'required|string',
        ]);
        
        Setting::set('charter_text_fr', $this->charter_text_fr);
        Setting::set('charter_text_en', $this->charter_text_en);
        Setting::set('charter_text_it', $this->charter_text_it);
        Setting::set('charter_text_es', $this->charter_text_es);
        
        AuditService::log('settings_updated', 'content', [
            'section' => 'charter',
            'languages_updated' => ['fr', 'en', 'it', 'es'],
        ]);
        
        session()->flash('message', 'Textes de la charte mis à jour avec succès.');
    }

    public function saveEmailSettings()
    {
        $this->validate([
            'email_from_name' => 'required|string|max:255',
            'email_from_address' => 'required|email',
        ]);
        
        Setting::set('email_from_name', $this->email_from_name);
        Setting::set('email_from_address', $this->email_from_address);
        
        AuditService::log('settings_updated', 'system', [
            'section' => 'email',
            'changes' => [
                'email_from_name' => $this->email_from_name,
                'email_from_address' => $this->email_from_address,
            ],
        ]);
        
        session()->flash('message', 'Paramètres email mis à jour avec succès.');
    }

    public function saveUserSettings()
    {
        $this->validate([
            'guest_access_duration_hours' => 'required|integer|min:1|max:168',
            'guest_validation_timeout_minutes' => 'required|integer|min:5|max:120',
            'consultant_max_duration_days' => 'required|integer|min:1|max:730',
        ]);
        
        Setting::set('guest_access_duration_hours', $this->guest_access_duration_hours);
        Setting::set('guest_validation_timeout_minutes', $this->guest_validation_timeout_minutes);
        Setting::set('consultant_max_duration_days', $this->consultant_max_duration_days);
        
        AuditService::log('settings_updated', 'system', [
            'section' => 'users',
            'changes' => [
                'guest_access_duration_hours' => $this->guest_access_duration_hours,
                'guest_validation_timeout_minutes' => $this->guest_validation_timeout_minutes,
                'consultant_max_duration_days' => $this->consultant_max_duration_days,
            ],
        ]);
        
        session()->flash('message', 'Paramètres utilisateurs mis à jour avec succès.');
    }
    
    public function exportAuditLogs()
    {
        // Only super admins can export audit logs
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        AuditService::log('audit_logs_exported', 'security', [
            'exported_by' => auth()->guard('admin')->id(),
        ]);
        
        // TODO: Implement Excel export using Maatwebsite/Excel
        session()->flash('message', 'Export des logs d\'audit en cours...');
    }
    
    public function clearCache()
    {
        // Only super admins can clear cache
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');
        
        AuditService::log('cache_cleared', 'system', [
            'cleared_by' => auth()->guard('admin')->id(),
        ]);
        
        session()->flash('message', 'Cache vidé avec succès.');
    }
    
    private function getDefaultCharter($locale)
    {
        $charters = [
            'fr' => "Conditions d'utilisation du réseau WiFi\n\nEn accédant à ce réseau, vous acceptez de:\n- Respecter la législation en vigueur\n- Ne pas accéder à des contenus illégaux\n- Ne pas compromettre la sécurité du réseau\n- Utiliser le réseau de manière responsable\n\nTout abus sera sanctionné.",
            'en' => "WiFi Network Terms of Use\n\nBy accessing this network, you agree to:\n- Comply with applicable laws\n- Not access illegal content\n- Not compromise network security\n- Use the network responsibly\n\nAny abuse will be sanctioned.",
            'it' => "Termini di utilizzo della rete WiFi\n\nAccedendo a questa rete, accetti di:\n- Rispettare le leggi vigenti\n- Non accedere a contenuti illegali\n- Non compromettere la sicurezza della rete\n- Utilizzare la rete in modo responsabile\n\nOgni abuso sarà sanzionato.",
            'es' => "Términos de uso de la red WiFi\n\nAl acceder a esta red, acepta:\n- Cumplir con las leyes aplicables\n- No acceder a contenido ilegal\n- No comprometer la seguridad de la red\n- Usar la red de manera responsable\n\nCualquier abuso será sancionado.",
        ];
        
        return $charters[$locale] ?? $charters['fr'];
    }
}