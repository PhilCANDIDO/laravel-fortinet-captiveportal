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
        Schema::table('fortigate_settings', function (Blueprint $table) {
            $table->string('captive_portal_url')->nullable()->after('user_group');
        });
        
        // Set default value
        \DB::table('fortigate_settings')->where('id', 1)->update([
            'captive_portal_url' => 'https://192.168.1.1:1003/fgtauth'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fortigate_settings', function (Blueprint $table) {
            $table->dropColumn('captive_portal_url');
        });
    }
};