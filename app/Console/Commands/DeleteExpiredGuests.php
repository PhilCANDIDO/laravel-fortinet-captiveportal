<?php

namespace App\Console\Commands;

use App\Jobs\DeleteExpiredGuestsJob;
use Illuminate\Console\Command;

class DeleteExpiredGuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:delete-expired 
                            {--force : Run immediately without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all expired guest accounts from the system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete all expired guest accounts. Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        $this->info('Processing expired guest accounts...');
        
        // Dispatch the job synchronously for immediate execution
        DeleteExpiredGuestsJob::dispatchSync();
        
        $this->info('Expired guest deletion job completed. Check logs for details.');
        
        return Command::SUCCESS;
    }
}