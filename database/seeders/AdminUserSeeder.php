<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminUser;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a super admin user
        AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'password' => 'SuperAdmin@Password123!',
            'email_verified_at' => now(),
            'is_active' => true,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
        ]);
        
        // Create a test admin user without MFA enabled
        AdminUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => AdminUser::ROLE_ADMIN,
            'password' => 'Admin@Password123!', // The model will hash this automatically
            'email_verified_at' => now(),
            'is_active' => true,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
        ]);
        
        // Create a test admin with MFA enabled (for testing)
        $adminWithMfa = AdminUser::create([
            'name' => 'Admin MFA',
            'email' => 'admin.mfa@example.com',
            'password' => 'Admin@Password123!', // The model will hash this automatically
            'email_verified_at' => now(),
            'is_active' => true,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
        ]);
        
        // Enable MFA with a test secret (for development only)
        // In production, users should set up their own MFA
        $adminWithMfa->update([
            'google2fa_secret' => encrypt('JBSWY3DPEHPK3PXP'), // Test secret
            'google2fa_enabled' => false, // Set to false initially so you can test the setup flow
            'google2fa_enabled_at' => null,
        ]);
        
        $this->command->info('Admin users created:');
        $this->command->info('');
        $this->command->info('Super Admin:');
        $this->command->info('Email: superadmin@example.com');
        $this->command->info('Password: SuperAdmin@Password123!');
        $this->command->info('');
        $this->command->info('Regular Admin:');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: Admin@Password123!');
        $this->command->info('');
        $this->command->info('Admin with MFA (for testing):');
        $this->command->info('Email: admin.mfa@example.com');
        $this->command->info('Password: Admin@Password123!');
        $this->command->info('MFA: Disabled (can be enabled after login)');
    }
}