<?php

namespace App\Jobs;

use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(UserService $userService): void
    {
        Log::info('Starting user expiration check');
        
        $count = $userService->handleExpiredUsers();
        
        if ($count > 0) {
            Log::info("Expired {$count} user accounts");
        }
        
        // Also cleanup unvalidated guests
        $guestCount = $userService->cleanupUnvalidatedGuests();
        
        if ($guestCount > 0) {
            Log::info("Cleaned up {$guestCount} unvalidated guest accounts");
        }
    }
}