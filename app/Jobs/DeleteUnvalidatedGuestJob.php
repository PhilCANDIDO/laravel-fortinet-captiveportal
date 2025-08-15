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

class DeleteUnvalidatedGuestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(UserService $userService): void
    {
        $user = User::find($this->userId);
        
        if (!$user) {
            Log::info('User not found for validation check', ['user_id' => $this->userId]);
            return;
        }
        
        // Only disable if still unvalidated guest
        if ($user->isGuest() && !$user->validated_at) {
            Log::info('Disabling unvalidated guest', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ]);
            
            // Disable the user instead of deleting
            $user->is_active = false;
            $user->status = User::STATUS_SUSPENDED;
            $user->status_reason = 'Email not validated within required time';
            $user->save();
            
            // Disable in FortiGate
            try {
                $fortiGateService = app(\App\Services\FortiGateService::class);
                if ($fortiGateService->isConfigured()) {
                    $fortiGateService->updateUser($user->fortigate_username, [
                        'status' => 'disable'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to disable user in FortiGate', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}