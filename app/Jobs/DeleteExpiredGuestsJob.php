<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteExpiredGuestsJob implements ShouldQueue
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
        Log::info('Starting expired guest deletion job');
        
        // Get all expired guest users
        $expiredGuests = User::where('user_type', User::TYPE_GUEST)
                            ->where('expires_at', '<=', now())
                            ->where('status', '!=', User::STATUS_DELETED)
                            ->get();
        
        $count = 0;
        
        foreach ($expiredGuests as $guest) {
            try {
                Log::info('Deleting expired guest', [
                    'user_id' => $guest->id,
                    'email' => $guest->email,
                    'fortigate_username' => $guest->fortigate_username,
                    'expired_at' => $guest->expires_at,
                    'hours_expired' => now()->diffInHours($guest->expires_at)
                ]);
                
                // Use the UserService to properly delete the user
                $userService->deleteUser($guest);
                $count++;
                
            } catch (\Exception $e) {
                Log::error('Failed to delete expired guest', [
                    'user_id' => $guest->id,
                    'email' => $guest->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if ($count > 0) {
            Log::info("Successfully deleted {$count} expired guest accounts");
        } else {
            Log::info('No expired guests to delete');
        }
    }
}