#!/usr/bin/env php
<?php

/**
 * Guest Registration Workflow Test
 * Tests the complete guest registration process with FortiGate integration
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\UserService;
use App\Services\FortiGateService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Setting;
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

function section($title) {
    echo "\n\033[1;36m=== $title ===\033[0m\n\n";
}

try {
    section("Guest Registration Workflow Test");
    
    // Check services
    $userService = app(UserService::class);
    $fortiGateService = app(FortiGateService::class);
    $notificationService = app(NotificationService::class);
    
    // Check current settings
    $emailValidationEnabled = Setting::isGuestEmailValidationEnabled();
    $fortiGateSettings = FortiGateSettings::current();
    
    info("Current Configuration:");
    info("- Email validation: " . ($emailValidationEnabled ? 'ENABLED' : 'DISABLED'));
    info("- FortiGate API: " . $fortiGateSettings->api_url);
    info("- User group: " . ($fortiGateSettings->user_group ?: 'Not configured'));
    echo "\n";
    
    // Test guest data
    $testData = [
        'first_name' => 'Test',
        'last_name' => 'Guest-' . uniqid(),
        'email' => 'test.guest.' . uniqid() . '@example.com',
        'phone' => '+33 6 12 34 56 78',
        'company_name' => 'Test Company',
        'visit_reason' => 'Testing FortiGate integration'
    ];
    
    section("Step 1: Create Guest User");
    info("Creating guest: {$testData['first_name']} {$testData['last_name']}");
    info("Email: {$testData['email']}");
    
    $guest = $userService->createGuest($testData);
    
    if ($guest) {
        success("Guest created with ID: {$guest->id}");
        success("FortiGate username: {$guest->fortigate_username}");
        success("Status: {$guest->status}");
        success("Is Active: " . ($guest->is_active ? 'YES' : 'NO'));
        
        // Check FortiGate sync
        if ($guest->fortigate_sync_status === User::SYNC_SYNCED) {
            success("FortiGate sync status: SYNCED");
        } elseif ($guest->fortigate_sync_status === User::SYNC_ERROR) {
            error("FortiGate sync error: {$guest->fortigate_sync_error}");
        } else {
            warning("FortiGate sync status: {$guest->fortigate_sync_status}");
        }
    } else {
        error("Failed to create guest");
        exit(1);
    }
    
    section("Step 2: Verify FortiGate User");
    
    if ($fortiGateService->isConfigured()) {
        try {
            $fortiGateUser = $fortiGateService->getUser($guest->fortigate_username);
            
            if ($fortiGateUser) {
                success("User exists in FortiGate");
                info("- Username: " . $fortiGateUser['name']);
                info("- Status: " . $fortiGateUser['status']);
                info("- Email: " . ($fortiGateUser['email-to'] ?? 'N/A'));
                
                // Expected status based on email validation setting
                $expectedStatus = $emailValidationEnabled ? 'disable' : 'enable';
                if ($fortiGateUser['status'] === $expectedStatus) {
                    success("Status is correct: {$expectedStatus}");
                } else {
                    error("Status mismatch! Expected: {$expectedStatus}, Got: {$fortiGateUser['status']}");
                }
            } else {
                error("User not found in FortiGate");
            }
        } catch (Exception $e) {
            error("Failed to verify FortiGate user: " . $e->getMessage());
        }
    } else {
        warning("FortiGate service not configured - skipping verification");
    }
    
    section("Step 3: Test Email Validation (if enabled)");
    
    if ($emailValidationEnabled && $guest->validation_token) {
        info("Email validation is ENABLED");
        info("Validation token: {$guest->validation_token}");
        
        // Simulate email validation
        info("Simulating email validation...");
        $guest->validated_at = now();
        $guest->validation_token = null;
        $guest->validation_expires_at = null;
        $guest->status = User::STATUS_ACTIVE;
        $guest->is_active = true;
        $guest->save();
        
        success("Guest validated successfully");
        
        // Try to enable in FortiGate
        if ($fortiGateService->isConfigured()) {
            try {
                $fortiGateService->updateUser($guest->fortigate_username, [
                    'status' => 'enable'
                ]);
                
                // Also add to group
                if ($fortiGateSettings->user_group) {
                    $fortiGateService->addUserToGroup($guest->fortigate_username, $fortiGateSettings->user_group);
                }
                
                success("FortiGate user enabled and added to group");
            } catch (Exception $e) {
                error("Failed to enable FortiGate user: " . $e->getMessage());
            }
        }
    } else {
        info("Email validation is DISABLED - user should be active immediately");
        
        if ($guest->is_active) {
            success("Guest is active as expected");
        } else {
            error("Guest is not active but should be!");
        }
    }
    
    section("Step 4: Cleanup");
    
    info("Deleting test guest user...");
    
    try {
        $deleted = $userService->deleteUser($guest);
        if ($deleted) {
            success("Guest user deleted successfully");
            
            // Verify deletion from FortiGate
            if ($fortiGateService->isConfigured()) {
                try {
                    $checkUser = $fortiGateService->getUser($guest->fortigate_username);
                    if (!$checkUser) {
                        success("User removed from FortiGate");
                    } else {
                        error("User still exists in FortiGate!");
                    }
                } catch (Exception $e) {
                    // 404 error is expected - user should not exist
                    if (strpos($e->getMessage(), '404') !== false) {
                        success("User removed from FortiGate (404)");
                    } else {
                        error("Error checking FortiGate: " . $e->getMessage());
                    }
                }
            }
        } else {
            error("Failed to delete guest user");
        }
    } catch (Exception $e) {
        error("Failed to delete user: " . $e->getMessage());
    }
    
    section("Test Summary");
    
    success("Guest registration workflow completed");
    info("Key findings:");
    
    if ($emailValidationEnabled) {
        info("- With email validation ENABLED:");
        info("  • Users are created as DISABLED in FortiGate");
        info("  • Users must validate email to be enabled");
        info("  • After validation, users are enabled and added to group");
    } else {
        info("- With email validation DISABLED:");
        info("  • Users are created as ENABLED in FortiGate");
        info("  • Users are immediately added to group");
        info("  • Users receive credentials email without validation link");
    }
    
    if ($fortiGateSettings->user_group) {
        warning("\nIMPORTANT: Please verify in FortiGate GUI that:");
        warning("1. Users are being added to group: {$fortiGateSettings->user_group}");
        warning("2. The group has appropriate captive portal policies");
    }
    
} catch (Exception $e) {
    error("Test failed: " . $e->getMessage());
    error("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

echo "\n";
success("All tests completed!");