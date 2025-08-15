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
        // Add email validation settings
        \App\Models\Setting::firstOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'users',
                'description' => 'Enable email validation for guest users',
                'is_public' => false
            ]
        );
        
        \App\Models\Setting::firstOrCreate(
            ['key' => 'guest_validation_delay_minutes'],
            [
                'value' => '30',
                'type' => 'integer',
                'group' => 'users',
                'description' => 'Delay in minutes before disabling unvalidated guest accounts',
                'is_public' => false
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Setting::whereIn('key', [
            'guest_email_validation_enabled',
            'guest_validation_delay_minutes'
        ])->delete();
    }
};
