<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendExpirationReminderJob implements ShouldQueue
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
    public $timeout = 60;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get users expiring in the next 7 days
        $expiringUsers = User::where('expires_at', '>', Carbon::now())
                             ->where('expires_at', '<=', Carbon::now()->addDays(7))
                             ->whereIn('user_type', [User::TYPE_CONSULTANT, User::TYPE_GUEST])
                             ->where('is_active', true)
                             ->get();
        
        if ($expiringUsers->isEmpty()) {
            return;
        }
        
        Log::info('Sending expiration reminders', ['count' => $expiringUsers->count()]);
        
        $notificationService = app(NotificationService::class);
        
        foreach ($expiringUsers as $user) {
            try {
                // Calculate days until expiration
                $daysUntilExpiration = Carbon::now()->diffInDays($user->expires_at, false);
                
                // Send reminder based on days remaining
                if ($daysUntilExpiration == 7) {
                    $notificationService->sendExpirationReminder($user, 7);
                } elseif ($daysUntilExpiration == 3) {
                    $notificationService->sendExpirationReminder($user, 3);
                } elseif ($daysUntilExpiration == 1) {
                    $notificationService->sendExpirationReminder($user, 1);
                }
                
            } catch (\Exception $e) {
                Log::error('Failed to send expiration reminder', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}