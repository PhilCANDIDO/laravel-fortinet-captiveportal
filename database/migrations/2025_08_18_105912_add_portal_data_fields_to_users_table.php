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
            // Portal data storage
            $table->json('portal_data')->nullable()->after('fortigate_sync_error')
                ->comment('Stores FortiGate captive portal data for auto-authentication');
            
            // Additional network information from portal
            $table->string('network_ssid', 100)->nullable()->after('portal_data')
                ->comment('Network SSID from captive portal');
            
            // Visit reason for guests
            $table->text('visit_reason')->nullable()->after('network_ssid')
                ->comment('Reason for guest visit');
            
            // Add indexes for better query performance
            $table->index('network_ssid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['portal_data', 'network_ssid', 'visit_reason']);
        });
    }
};