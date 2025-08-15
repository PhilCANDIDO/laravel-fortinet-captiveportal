<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // User type: employee, consultant, guest
            $table->enum('user_type', ['employee', 'consultant', 'guest'])->default('guest')->after('email');
            
            // Company information
            $table->string('company_name')->nullable()->after('user_type');
            $table->string('department')->nullable()->after('company_name');
            
            // Guest specific fields
            $table->string('sponsor_email')->nullable()->after('department');
            $table->string('sponsor_name')->nullable()->after('sponsor_email');
            
            // Validation fields
            $table->string('validation_token')->nullable()->after('password');
            $table->timestamp('validation_expires_at')->nullable()->after('validation_token');
            $table->timestamp('validated_at')->nullable()->after('validation_expires_at');
            
            // Expiration management
            $table->timestamp('expires_at')->nullable()->after('validated_at');
            $table->boolean('is_active')->default(false)->after('expires_at');
            
            // Charter acceptance
            $table->timestamp('charter_accepted_at')->nullable()->after('is_active');
            $table->string('charter_version')->nullable()->after('charter_accepted_at');
            
            // FortiGate synchronization
            $table->string('fortigate_username')->nullable()->unique()->after('charter_version');
            $table->enum('fortigate_sync_status', ['pending', 'synced', 'error', 'not_required'])->default('pending')->after('fortigate_username');
            $table->timestamp('fortigate_synced_at')->nullable()->after('fortigate_sync_status');
            $table->text('fortigate_sync_error')->nullable()->after('fortigate_synced_at');
            
            // Contact information
            $table->string('phone')->nullable()->after('email');
            $table->string('mobile')->nullable()->after('phone');
            
            // Access tracking
            $table->string('registration_ip')->nullable()->after('fortigate_sync_error');
            $table->string('last_login_ip')->nullable()->after('registration_ip');
            $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
            $table->integer('login_count')->default(0)->after('last_login_at');
            
            // Account status
            $table->enum('status', ['pending', 'active', 'suspended', 'expired', 'deleted'])->default('pending')->after('login_count');
            $table->text('status_reason')->nullable()->after('status');
            
            // Notes for administrators
            $table->text('admin_notes')->nullable()->after('status_reason');
            
            // Indexes for performance
            $table->index('user_type');
            $table->index('expires_at');
            $table->index('is_active');
            $table->index('status');
            $table->index('fortigate_sync_status');
            $table->index('validation_token');
            $table->index(['user_type', 'is_active']);
            $table->index(['expires_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['user_type', 'is_active']);
            $table->dropIndex(['expires_at', 'is_active']);
            $table->dropIndex(['user_type']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['status']);
            $table->dropIndex(['fortigate_sync_status']);
            $table->dropIndex(['validation_token']);
            
            // Drop columns
            $table->dropColumn([
                'user_type',
                'company_name',
                'department',
                'sponsor_email',
                'sponsor_name',
                'validation_token',
                'validation_expires_at',
                'validated_at',
                'expires_at',
                'is_active',
                'charter_accepted_at',
                'charter_version',
                'fortigate_username',
                'fortigate_sync_status',
                'fortigate_synced_at',
                'fortigate_sync_error',
                'phone',
                'mobile',
                'registration_ip',
                'last_login_ip',
                'last_login_at',
                'login_count',
                'status',
                'status_reason',
                'admin_notes',
            ]);
        });
    }
};