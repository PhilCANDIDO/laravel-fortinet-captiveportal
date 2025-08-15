<?php

return [
    'status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'expired' => 'Expired',
        'deleted' => 'Deleted',
    ],
    
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'user_type' => 'User Type',
        'company_name' => 'Company',
        'department' => 'Department',
        'sponsor_name' => 'Sponsor Name',
        'sponsor_email' => 'Sponsor Email',
        'phone' => 'Phone',
        'mobile' => 'Mobile',
        'expires_at' => 'Expiration Date',
        'validated_at' => 'Validated On',
        'charter_accepted_at' => 'Charter Accepted On',
        'last_login_at' => 'Last Login',
        'login_count' => 'Login Count',
        'status' => 'Status',
        'admin_notes' => 'Admin Notes',
    ],
    
    'actions' => [
        'create' => 'Create User',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'suspend' => 'Suspend',
        'reactivate' => 'Reactivate',
        'extend' => 'Extend',
        'sync' => 'Sync with FortiGate',
        'resend_validation' => 'Resend Validation Email',
        'view_sessions' => 'View Sessions',
    ],
    
    'messages' => [
        'created' => 'User created successfully',
        'updated' => 'User updated successfully',
        'deleted' => 'User deleted successfully',
        'suspended' => 'User suspended',
        'reactivated' => 'User reactivated',
        'extended' => 'Expiration date extended',
        'validation_sent' => 'Validation email sent',
        'synced' => 'FortiGate synchronization successful',
        'sync_failed' => 'FortiGate synchronization failed',
    ],
    
    'validation' => [
        'expired' => 'Validation link has expired',
        'invalid_token' => 'Invalid validation token',
        'already_validated' => 'Email already validated',
        'success' => 'Email validated successfully',
        'required' => 'Email validation required',
        'time_remaining' => 'Time remaining to validate: :minutes minutes',
    ],
    
    'expiration' => [
        'never' => 'Never',
        'expired' => 'Expired',
        'expires_in' => 'Expires in :time',
        'expires_today' => 'Expires today',
        'expires_tomorrow' => 'Expires tomorrow',
    ],
];