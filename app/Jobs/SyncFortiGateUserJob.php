<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFortiGateUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

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
    public function handle(): void
    {
        $user = User::find($this->userId);
        
        if (!$user) {
            Log::warning('User not found for FortiGate sync', ['user_id' => $this->userId]);
            return;
        }
        
        Log::info('Starting FortiGate sync for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'type' => $user->user_type,
        ]);
        
        $result = $user->syncWithFortiGate();
        
        if ($result) {
            Log::info('FortiGate sync successful', [
                'user_id' => $user->id,
                'fortigate_username' => $user->fortigate_username,
            ]);
        } else {
            Log::error('FortiGate sync failed', [
                'user_id' => $user->id,
                'error' => $user->fortigate_sync_error,
            ]);
            
            // Throw exception to trigger retry
            throw new \Exception('FortiGate sync failed: ' . $user->fortigate_sync_error);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FortiGate sync job failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
        
        // Mark user as sync error
        $user = User::find($this->userId);
        if ($user) {
            $user->updateFortiGateSync(User::SYNC_ERROR, $exception->getMessage());
        }
    }
}