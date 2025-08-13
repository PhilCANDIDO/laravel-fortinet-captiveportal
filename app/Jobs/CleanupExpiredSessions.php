<?php

namespace App\Jobs;

use App\Models\AdminSession;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupExpiredSessions implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        // Delete sessions that haven't been active for more than 15 minutes
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15)->timestamp;
        
        AdminSession::where('last_activity', '<', $fifteenMinutesAgo)
            ->orWhere('is_active', false)
            ->orWhere(function ($query) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            })
            ->delete();
            
        // Also clean up expired password reset tokens (older than 30 minutes)
        \DB::table('admin_password_reset_tokens')
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->delete();
    }
}