<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class SyncUsersWithFortiGate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-fortigate {--force : Force sync all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync pending users with FortiGate';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        \Log::info('===== JOB STARTED: SyncUsersWithFortiGate =====');
        $this->info('Starting FortiGate user synchronization...');

        if ($this->option('force')) {
            $this->warn('Force sync enabled - all users will be synced');
            \Log::info('Force sync enabled');
            // Reset all users to pending sync
            \App\Models\User::query()->update(['fortigate_sync_status' => \App\Models\User::SYNC_PENDING]);
        }

        $count = $userService->syncPendingUsers();

        if ($count > 0) {
            $this->info("Successfully synced {$count} users with FortiGate");
            \Log::info("SyncUsersWithFortiGate completed: {$count} users synced");
        } else {
            $this->info('No users needed synchronization');
            \Log::info('SyncUsersWithFortiGate completed: No users needed sync');
        }

        \Log::info('===== JOB FINISHED: SyncUsersWithFortiGate =====');
        
        // Show statistics
        if ($this->output->isVerbose()) {
            $stats = $userService->getStatistics();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Users', $stats['total']],
                    ['Synced', $stats['sync_status']['synced']],
                    ['Pending', $stats['sync_status']['pending']],
                    ['Error', $stats['sync_status']['error']],
                ]
            );
        }
        
        return 0;
    }
}