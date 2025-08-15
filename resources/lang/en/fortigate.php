<?php

return [
    'user_types' => [
        'employee' => 'Employee',
        'consultant' => 'Consultant',
        'guest' => 'Guest',
    ],
    
    'errors' => [
        'general' => 'An error occurred with FortiGate API',
        'unauthorized' => 'FortiGate authentication failed',
        'forbidden' => 'Access denied to FortiGate resource',
        'not_found' => 'FortiGate resource not found',
        'rate_limited' => 'Too many requests to FortiGate API',
        'server_error' => 'FortiGate server error',
        'timeout' => 'FortiGate request timeout',
        'connection_failed' => 'Unable to connect to FortiGate',
        'user_exists' => 'User already exists in FortiGate',
        'user_not_found' => 'User not found in FortiGate',
        'sync_failed' => 'FortiGate synchronization failed',
    ],
    
    'success' => [
        'user_created' => 'User successfully created in FortiGate',
        'user_updated' => 'User updated in FortiGate',
        'user_deleted' => 'User deleted from FortiGate',
        'user_enabled' => 'User enabled in FortiGate',
        'user_disabled' => 'User disabled in FortiGate',
        'session_terminated' => 'User session terminated',
        'sync_completed' => 'FortiGate synchronization completed',
    ],
    
    'status' => [
        'synced' => 'Synced',
        'pending' => 'Pending',
        'error' => 'Error',
        'not_synced' => 'Not synced',
    ],
    
    'actions' => [
        'sync' => 'Synchronize',
        'force_sync' => 'Force synchronization',
        'terminate_session' => 'Terminate session',
        'view_sessions' => 'View sessions',
    ],
    
    'session' => [
        'active' => 'Active session',
        'inactive' => 'No active session',
        'terminated' => 'Session terminated',
        'ip_address' => 'IP Address',
        'start_time' => 'Session start',
        'duration' => 'Duration',
    ],
];