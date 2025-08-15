#!/usr/bin/env php
<?php

/**
 * FortiGate Connection Test Script
 * Tests the 2-step user creation and group assignment process
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FortiGateService;
use App\Models\FortiGateSettings;

// Colors for output
function success($message) {
    echo "\033[32m✓ $message\033[0m\n";
}

function error($message) {
    echo "\033[31m✗ $message\033[0m\n";
}

function info($message) {
    echo "\033[34mℹ $message\033[0m\n";
}

function warning($message) {
    echo "\033[33m⚠ $message\033[0m\n";
}

try {
    info("Starting FortiGate Connection Test...\n");
    
    // Initialize FortiGate service
    $fortiGateService = app(FortiGateService::class);
    
    // Check if service is configured
    if (!$fortiGateService->isConfigured()) {
        error("FortiGate service is not configured. Please check your settings.");
        exit(1);
    }
    
    $settings = FortiGateSettings::current();
    info("API URL: " . $settings->api_url);
    info("User Group: " . ($settings->user_group ?: 'Not configured'));
    echo "\n";
    
    // Test 1: Health Check
    info("Test 1: Health Check");
    $health = $fortiGateService->healthCheck();
    if ($health['status'] === 'healthy') {
        success("FortiGate API is healthy (Response time: {$health['response_time']}s)");
    } else {
        error("FortiGate API is unhealthy: " . $health['error']);
        exit(1);
    }
    echo "\n";
    
    // Test 2: Create Test User
    info("Test 2: Creating test user");
    $testUsername = 'guest-test-' . uniqid();
    $testPassword = 'TestPass123!@#';
    $testEmail = 'test-' . uniqid() . '@example.com';
    
    $userData = [
        'username' => $testUsername,
        'password' => $testPassword,
        'email' => $testEmail,
        'status' => 'enable'
    ];
    
    try {
        $result = $fortiGateService->createUser($userData);
        success("User created successfully: $testUsername");
        
        // Check if user was added to group
        if (!empty($settings->user_group)) {
            info("Verifying group membership...");
            sleep(2); // Give FortiGate time to process
            
            // Try to get user info
            $userInfo = $fortiGateService->getUser($testUsername);
            if ($userInfo) {
                success("User exists in FortiGate");
                
                // The group membership was handled in the 2-step process
                info("Group assignment was attempted for: {$settings->user_group}");
                warning("Note: Group verification requires checking FortiGate GUI or group endpoint directly");
            }
        }
    } catch (Exception $e) {
        error("Failed to create user: " . $e->getMessage());
        
        // Check if it's a group-related error
        if (strpos($e->getMessage(), 'group') !== false) {
            warning("Group assignment failed. Please verify:");
            warning("1. The group '{$settings->user_group}' exists in FortiGate");
            warning("2. The API token has permission to modify groups");
        }
    }
    echo "\n";
    
    // Test 3: Update User (Enable/Disable)
    info("Test 3: Testing user enable/disable");
    try {
        // Disable user
        $fortiGateService->updateUser($testUsername, ['status' => 'disable']);
        success("User disabled successfully");
        
        // Enable user
        $fortiGateService->updateUser($testUsername, ['status' => 'enable']);
        success("User enabled successfully");
    } catch (Exception $e) {
        error("Failed to update user: " . $e->getMessage());
    }
    echo "\n";
    
    // Test 4: Delete Test User
    info("Test 4: Cleaning up test user");
    try {
        $deleted = $fortiGateService->deleteUser($testUsername);
        if ($deleted) {
            success("Test user deleted successfully");
        } else {
            warning("User deletion returned false");
        }
    } catch (Exception $e) {
        error("Failed to delete user: " . $e->getMessage());
    }
    echo "\n";
    
    // Test 5: Get Active Sessions
    info("Test 5: Getting active sessions");
    try {
        $sessions = $fortiGateService->getActiveSessions();
        success("Retrieved " . count($sessions) . " active session(s)");
        
        if (count($sessions) > 0) {
            info("Sample session info:");
            $sample = array_slice($sessions, 0, 3);
            foreach ($sample as $session) {
                info("  - User: " . ($session['username'] ?? 'N/A') . 
                     ", IP: " . ($session['ip'] ?? 'N/A'));
            }
        }
    } catch (Exception $e) {
        warning("Could not retrieve sessions: " . $e->getMessage());
    }
    echo "\n";
    
    // Summary
    info("Test Summary:");
    success("FortiGate connection is working");
    
    if (!empty($settings->user_group)) {
        warning("Group assignment configured for: {$settings->user_group}");
        info("Please verify in FortiGate GUI that users are being added to the group");
        info("If group assignment fails, check:");
        info("  1. Group exists in FortiGate");
        info("  2. API token has group modification permissions");
        info("  3. Group name matches exactly (case-sensitive)");
    } else {
        warning("No user group configured - users won't be added to any group");
    }
    
} catch (Exception $e) {
    error("Test failed: " . $e->getMessage());
    error("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

echo "\n";
success("All tests completed!");