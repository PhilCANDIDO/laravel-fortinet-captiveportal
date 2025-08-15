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
        Schema::create('fortigate_settings', function (Blueprint $table) {
            $table->id();
            
            // API Connection Settings
            $table->string('api_url')->default('https://192.168.1.1/api/v2');
            $table->text('api_token')->nullable();
            $table->boolean('verify_ssl')->default(false);
            $table->integer('timeout')->default(30);
            
            // User Management Settings
            $table->string('user_group')->default('captive_portal_users');
            $table->integer('default_password_length')->default(12);
            
            // Session Management
            $table->integer('session_timeout')->default(86400); // 24 hours
            $table->integer('guest_session_timeout')->default(86400); // 24 hours
            $table->integer('consultant_session_timeout')->default(2592000); // 30 days
            
            // Retry Configuration
            $table->integer('retry_max_attempts')->default(3);
            $table->integer('retry_initial_delay')->default(1000); // milliseconds
            $table->integer('retry_max_delay')->default(10000); // milliseconds
            $table->integer('retry_multiplier')->default(2);
            
            // Circuit Breaker Configuration
            $table->integer('circuit_breaker_failure_threshold')->default(5);
            $table->integer('circuit_breaker_recovery_time')->default(60); // seconds
            $table->integer('circuit_breaker_success_threshold')->default(2);
            
            // Cache Configuration
            $table->boolean('cache_enabled')->default(true);
            $table->integer('cache_ttl')->default(300); // 5 minutes
            $table->string('cache_prefix')->default('fortigate');
            
            // Logging
            $table->boolean('logging_enabled')->default(true);
            $table->string('log_channel')->default('stack');
            $table->boolean('log_requests')->default(true);
            $table->boolean('log_responses')->default(false);
            
            // Performance Monitoring
            $table->boolean('monitoring_enabled')->default(true);
            $table->string('metrics_prefix')->default('fortigate');
            
            // Connection Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connection_test')->nullable();
            $table->boolean('last_connection_status')->default(false);
            $table->text('last_connection_error')->nullable();
            
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('fortigate_settings')->insert([
            'api_url' => env('FORTIGATE_API_URL', 'https://172.20.0.254/api/v2'),
            'api_token' => env('FORTIGATE_API_TOKEN', '65fh64qwsd11jgmfbb3x5d61G9hy3w'),
            'verify_ssl' => env('FORTIGATE_VERIFY_SSL', false),
            'timeout' => env('FORTIGATE_TIMEOUT', 30),
            'user_group' => env('FORTIGATE_USER_GROUP', 'captive_portal_users'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fortigate_settings');
    }
};