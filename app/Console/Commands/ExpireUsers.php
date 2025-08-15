<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class ExpireUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:expire {--cleanup : Also cleanup unvalidated guests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire users whose expiration date has passed';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $this->info('Checking for expired users...');
        
        $expiredCount = $userService->handleExpiredUsers();
        
        if ($expiredCount > 0) {
            $this->info("Expired {$expiredCount} user accounts");
        } else {
            $this->info('No users needed to be expired');
        }
        
        if ($this->option('cleanup')) {
            $this->info('Cleaning up unvalidated guests...');
            
            $cleanupCount = $userService->cleanupUnvalidatedGuests();
            
            if ($cleanupCount > 0) {
                $this->info("Deleted {$cleanupCount} unvalidated guest accounts");
            } else {
                $this->info('No unvalidated guests to cleanup');
            }
        }
        
        return 0;
    }
}