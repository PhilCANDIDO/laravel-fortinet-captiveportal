<?php

namespace App\Livewire\Admin;

use App\Models\FortiGateSettings as FortiGateSettingsModel;
use App\Services\AuditService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class FortiGateSettings extends Component
{
    public FortiGateSettingsModel $settings;
    
    // Connection settings
    public $api_url;
    public $api_token;
    public $verify_ssl;
    public $timeout;
    public $is_active;
    
    // User management
    public $user_group;
    public $captive_portal_url;
    public $default_password_length;
    
    // Session management
    public $session_timeout;
    public $guest_session_timeout;
    public $consultant_session_timeout;
    
    // Retry configuration
    public $retry_max_attempts;
    public $retry_initial_delay;
    public $retry_max_delay;
    public $retry_multiplier;
    
    // Circuit breaker
    public $circuit_breaker_failure_threshold;
    public $circuit_breaker_recovery_time;
    public $circuit_breaker_success_threshold;
    
    // Cache
    public $cache_enabled;
    public $cache_ttl;
    
    // Logging
    public $logging_enabled;
    public $log_requests;
    public $log_responses;
    
    // Test connection
    public $testingConnection = false;
    public $connectionTestResult = null;
    
    protected $rules = [
        'api_url' => 'required|url',
        'api_token' => 'nullable|string|min:10',
        'verify_ssl' => 'boolean',
        'timeout' => 'required|integer|min:5|max:300',
        'is_active' => 'boolean',
        'user_group' => 'required|string',
        'captive_portal_url' => 'nullable|url',
        'default_password_length' => 'required|integer|min:8|max:32',
        'session_timeout' => 'required|integer|min:60',
        'guest_session_timeout' => 'required|integer|min:60',
        'consultant_session_timeout' => 'required|integer|min:60',
        'retry_max_attempts' => 'required|integer|min:1|max:10',
        'retry_initial_delay' => 'required|integer|min:100|max:10000',
        'retry_max_delay' => 'required|integer|min:1000|max:60000',
        'retry_multiplier' => 'required|numeric|min:1|max:5',
        'circuit_breaker_failure_threshold' => 'required|integer|min:1|max:20',
        'circuit_breaker_recovery_time' => 'required|integer|min:10|max:600',
        'circuit_breaker_success_threshold' => 'required|integer|min:1|max:10',
        'cache_enabled' => 'boolean',
        'cache_ttl' => 'required|integer|min:0|max:3600',
        'logging_enabled' => 'boolean',
        'log_requests' => 'boolean',
        'log_responses' => 'boolean',
    ];
    
    protected $messages = [
        'api_url.required' => 'L\'URL de l\'API FortiGate est requise',
        'api_url.url' => 'L\'URL de l\'API doit être valide',
        'api_token.min' => 'Le token API doit contenir au moins 10 caractères',
        'timeout.min' => 'Le délai d\'attente minimum est de 5 secondes',
        'timeout.max' => 'Le délai d\'attente maximum est de 300 secondes',
    ];
    
    public function mount()
    {
        $this->settings = FortiGateSettingsModel::current();
        $this->loadSettings();
    }
    
    protected function loadSettings()
    {
        $this->api_url = $this->settings->api_url;
        $this->api_token = $this->settings->getDecryptedApiToken();
        $this->verify_ssl = $this->settings->verify_ssl;
        $this->timeout = $this->settings->timeout;
        $this->is_active = $this->settings->is_active;
        
        $this->user_group = $this->settings->user_group;
        $this->captive_portal_url = $this->settings->captive_portal_url;
        $this->default_password_length = $this->settings->default_password_length;
        
        $this->session_timeout = $this->settings->session_timeout;
        $this->guest_session_timeout = $this->settings->guest_session_timeout;
        $this->consultant_session_timeout = $this->settings->consultant_session_timeout;
        
        $this->retry_max_attempts = $this->settings->retry_max_attempts;
        $this->retry_initial_delay = $this->settings->retry_initial_delay;
        $this->retry_max_delay = $this->settings->retry_max_delay;
        $this->retry_multiplier = $this->settings->retry_multiplier;
        
        $this->circuit_breaker_failure_threshold = $this->settings->circuit_breaker_failure_threshold;
        $this->circuit_breaker_recovery_time = $this->settings->circuit_breaker_recovery_time;
        $this->circuit_breaker_success_threshold = $this->settings->circuit_breaker_success_threshold;
        
        $this->cache_enabled = $this->settings->cache_enabled;
        $this->cache_ttl = $this->settings->cache_ttl;
        
        $this->logging_enabled = $this->settings->logging_enabled;
        $this->log_requests = $this->settings->log_requests;
        $this->log_responses = $this->settings->log_responses;
    }
    
    protected function updateSettings($showFlash = true)
    {
        $this->validate();
        
        // Update settings
        $this->settings->update([
            'api_url' => $this->api_url,
            'api_token' => $this->api_token,
            'verify_ssl' => $this->verify_ssl,
            'timeout' => $this->timeout,
            'is_active' => $this->is_active,
            'user_group' => $this->user_group,
            'captive_portal_url' => $this->captive_portal_url,
            'default_password_length' => $this->default_password_length,
            'session_timeout' => $this->session_timeout,
            'guest_session_timeout' => $this->guest_session_timeout,
            'consultant_session_timeout' => $this->consultant_session_timeout,
            'retry_max_attempts' => $this->retry_max_attempts,
            'retry_initial_delay' => $this->retry_initial_delay,
            'retry_max_delay' => $this->retry_max_delay,
            'retry_multiplier' => $this->retry_multiplier,
            'circuit_breaker_failure_threshold' => $this->circuit_breaker_failure_threshold,
            'circuit_breaker_recovery_time' => $this->circuit_breaker_recovery_time,
            'circuit_breaker_success_threshold' => $this->circuit_breaker_success_threshold,
            'cache_enabled' => $this->cache_enabled,
            'cache_ttl' => $this->cache_ttl,
            'logging_enabled' => $this->logging_enabled,
            'log_requests' => $this->log_requests,
            'log_responses' => $this->log_responses,
        ]);
        
        if ($showFlash) {
            session()->flash('success', 'Les paramètres FortiGate ont été mis à jour avec succès');
        }
    }
    
    public function save(AuditService $auditService)
    {
        try {
            $this->updateSettings(true);
            
            // Log the action
            $auditService->log(
                'fortigate_settings_updated',
                'FortiGate settings updated',
                ['api_url' => $this->api_url, 'is_active' => $this->is_active]
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to update FortiGate settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }
    
    public function testConnection()
    {
        $this->testingConnection = true;
        $this->connectionTestResult = null;
        
        try {
            // Update settings without showing flash message
            $this->updateSettings(false);
            
            // Test connection
            $result = $this->settings->testConnection();
            
            if ($result) {
                $this->connectionTestResult = [
                    'success' => true,
                    'message' => 'Connexion réussie à FortiGate',
                    'details' => 'API accessible et authentification valide',
                ];
            } else {
                $this->connectionTestResult = [
                    'success' => false,
                    'message' => 'Échec de la connexion à FortiGate',
                    'details' => $this->settings->last_connection_error,
                ];
            }
            
        } catch (\Exception $e) {
            $this->connectionTestResult = [
                'success' => false,
                'message' => 'Erreur lors du test de connexion',
                'details' => $e->getMessage(),
            ];
        } finally {
            $this->testingConnection = false;
        }
    }
    
    public function toggleService()
    {
        $this->is_active = !$this->is_active;
        
        try {
            $this->updateSettings(false);
            
            // Log the action
            app(AuditService::class)->log(
                'fortigate_service_toggled',
                $this->is_active ? 'FortiGate service activated' : 'FortiGate service deactivated',
                ['is_active' => $this->is_active]
            );
            
            // Show specific message for toggle action
            session()->flash('success', $this->is_active 
                ? 'Service FortiGate activé avec succès' 
                : 'Service FortiGate désactivé avec succès'
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle FortiGate service', [
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Erreur lors du changement d\'état du service: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.admin.fortigate-settings')
            ->layout('layouts.admin');
    }
}