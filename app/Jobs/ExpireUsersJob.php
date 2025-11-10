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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(UserService $userService): void
    {
        Log::info('===== JOB STARTED: ExpireUsersJob =====');
        Log::info('Starting user expiration check');

        $count = $userService->handleExpiredUsers();

        if ($count > 0) {
            Log::info("Expired {$count} user accounts");
        } else {
            Log::info('No users to expire');
        }

        // Also cleanup unvalidated guests
        $guestCount = $userService->cleanupUnvalidatedGuests();

        if ($guestCount > 0) {
            Log::info("Cleaned up {$guestCount} unvalidated guest accounts");
        } else {
            Log::info('No unvalidated guests to clean up');
        }

        Log::info('===== JOB FINISHED: ExpireUsersJob =====');
    }
}