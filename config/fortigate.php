<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FortiGate API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FortiGate FortiOS 7.6.3 REST API integration
    | Used for managing captive portal users and sessions
    |
    */

    // API Connection Settings
    'api_url' => env('FORTIGATE_API_URL', 'https://192.168.1.1/api/v2'),
    'api_token' => env('FORTIGATE_API_TOKEN', ''),
    'verify_ssl' => env('FORTIGATE_VERIFY_SSL', false),
    'timeout' => env('FORTIGATE_TIMEOUT', 30),

    // User Management Settings
    'user_group' => env('FORTIGATE_USER_GROUP', 'captive_portal_users'),
    'default_password_length' => env('FORTIGATE_DEFAULT_PASSWORD_LENGTH', 12),
    
    // Session Management
    'session_timeout' => env('FORTIGATE_SESSION_TIMEOUT', 86400), // 24 hours in seconds
    'guest_session_timeout' => env('FORTIGATE_GUEST_SESSION_TIMEOUT', 86400), // 24 hours
    'consultant_session_timeout' => env('FORTIGATE_CONSULTANT_SESSION_TIMEOUT', 2592000), // 30 days
    
    // Retry Configuration
    'retry' => [
        'max_attempts' => env('FORTIGATE_RETRY_MAX_ATTEMPTS', 3),
        'initial_delay' => env('FORTIGATE_RETRY_INITIAL_DELAY', 1000), // milliseconds
        'max_delay' => env('FORTIGATE_RETRY_MAX_DELAY', 10000), // milliseconds
        'multiplier' => env('FORTIGATE_RETRY_MULTIPLIER', 2),
    ],
    
    // Circuit Breaker Configuration
    'circuit_breaker' => [
        'failure_threshold' => env('FORTIGATE_CB_FAILURE_THRESHOLD', 5),
        'recovery_time' => env('FORTIGATE_CB_RECOVERY_TIME', 60), // seconds
        'success_threshold' => env('FORTIGATE_CB_SUCCESS_THRESHOLD', 2),
    ],
    
    // Cache Configuration
    'cache' => [
        'enabled' => env('FORTIGATE_CACHE_ENABLED', true),
        'ttl' => env('FORTIGATE_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('FORTIGATE_CACHE_PREFIX', 'fortigate'),
    ],
    
    // Logging
    'logging' => [
        'enabled' => env('FORTIGATE_LOGGING_ENABLED', true),
        'channel' => env('FORTIGATE_LOG_CHANNEL', 'stack'),
        'log_requests' => env('FORTIGATE_LOG_REQUESTS', true),
        'log_responses' => env('FORTIGATE_LOG_RESPONSES', false),
    ],

    // Performance Monitoring
    'monitoring' => [
        'enabled' => env('FORTIGATE_MONITORING_ENABLED', true),
        'metrics_prefix' => env('FORTIGATE_METRICS_PREFIX', 'fortigate'),
    ],
];