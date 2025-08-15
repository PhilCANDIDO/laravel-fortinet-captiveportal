<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class FortiGateSettings extends Model
{
    protected $table = 'fortigate_settings';
    
    protected $fillable = [
        'api_url',
        'api_token',
        'verify_ssl',
        'timeout',
        'user_group',
        'captive_portal_url',
        'default_password_length',
        'session_timeout',
        'guest_session_timeout',
        'consultant_session_timeout',
        'retry_max_attempts',
        'retry_initial_delay',
        'retry_max_delay',
        'retry_multiplier',
        'circuit_breaker_failure_threshold',
        'circuit_breaker_recovery_time',
        'circuit_breaker_success_threshold',
        'cache_enabled',
        'cache_ttl',
        'cache_prefix',
        'logging_enabled',
        'log_channel',
        'log_requests',
        'log_responses',
        'monitoring_enabled',
        'metrics_prefix',
        'is_active',
        'last_connection_test',
        'last_connection_status',
        'last_connection_error',
    ];
    
    protected $casts = [
        'verify_ssl' => 'boolean',
        'cache_enabled' => 'boolean',
        'logging_enabled' => 'boolean',
        'log_requests' => 'boolean',
        'log_responses' => 'boolean',
        'monitoring_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_connection_status' => 'boolean',
        'last_connection_test' => 'datetime',
    ];
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear cache when settings are updated
        static::updated(function ($settings) {
            Cache::forget('fortigate_settings');
        });
    }
    
    /**
     * Get the current FortiGate settings (singleton pattern)
     */
    public static function current(): self
    {
        return Cache::remember('fortigate_settings', 3600, function () {
            return self::firstOrCreate([], [
                'api_url' => 'https://172.20.0.254/api/v2',
                'user_group' => 'captive_portal_users',
            ]);
        });
    }
    
    /**
     * Get decrypted API token
     */
    public function getDecryptedApiToken(): ?string
    {
        if (!$this->api_token) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->api_token);
        } catch (\Exception $e) {
            // If decryption fails, assume it's not encrypted (for backward compatibility)
            return $this->api_token;
        }
    }
    
    /**
     * Set encrypted API token
     */
    public function setApiTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_token'] = null;
        }
    }
    
    /**
     * Get configuration as array (for compatibility with config() calls)
     */
    public function toConfig(): array
    {
        return [
            'api_url' => $this->api_url,
            'api_token' => $this->getDecryptedApiToken(),
            'verify_ssl' => $this->verify_ssl,
            'timeout' => $this->timeout,
            'user_group' => $this->user_group,
            'default_password_length' => $this->default_password_length,
            'session_timeout' => $this->session_timeout,
            'guest_session_timeout' => $this->guest_session_timeout,
            'consultant_session_timeout' => $this->consultant_session_timeout,
            'retry' => [
                'max_attempts' => $this->retry_max_attempts,
                'initial_delay' => $this->retry_initial_delay,
                'max_delay' => $this->retry_max_delay,
                'multiplier' => $this->retry_multiplier,
            ],
            'circuit_breaker' => [
                'failure_threshold' => $this->circuit_breaker_failure_threshold,
                'recovery_time' => $this->circuit_breaker_recovery_time,
                'success_threshold' => $this->circuit_breaker_success_threshold,
            ],
            'cache' => [
                'enabled' => $this->cache_enabled,
                'ttl' => $this->cache_ttl,
                'prefix' => $this->cache_prefix,
            ],
            'logging' => [
                'enabled' => $this->logging_enabled,
                'channel' => $this->log_channel,
                'log_requests' => $this->log_requests,
                'log_responses' => $this->log_responses,
            ],
            'monitoring' => [
                'enabled' => $this->monitoring_enabled,
                'metrics_prefix' => $this->metrics_prefix,
            ],
        ];
    }
    
    /**
     * Test connection to FortiGate
     */
    public function testConnection(): bool
    {
        try {
            $service = new \App\Services\FortiGateService();
            $result = $service->healthCheck();
            
            $this->update([
                'last_connection_test' => now(),
                'last_connection_status' => $result['status'] === 'healthy',
                'last_connection_error' => $result['status'] !== 'healthy' ? $result['error'] : null,
            ]);
            
            return $result['status'] === 'healthy';
        } catch (\Exception $e) {
            $this->update([
                'last_connection_test' => now(),
                'last_connection_status' => false,
                'last_connection_error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}