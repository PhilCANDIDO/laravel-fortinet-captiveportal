<?php

namespace App\Console\Commands;

use App\Models\AdminSession;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupAdminSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-sessions {--all : Remove all sessions} {--old : Remove sessions older than 24 hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up admin sessions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $count = AdminSession::count();
            AdminSession::truncate();
            $this->info("Removed all {$count} admin sessions.");
        } elseif ($this->option('old')) {
            $oldSessions = AdminSession::where('last_activity', '<', Carbon::now()->subDay()->timestamp)->count();
            AdminSession::where('last_activity', '<', Carbon::now()->subDay()->timestamp)->delete();
            $this->info("Removed {$oldSessions} sessions older than 24 hours.");
        } else {
            // By default, remove inactive sessions older than 1 hour
            $inactiveSessions = AdminSession::where('is_active', false)
                ->where('last_activity', '<', Carbon::now()->subHour()->timestamp)
                ->count();
            
            AdminSession::where('is_active', false)
                ->where('last_activity', '<', Carbon::now()->subHour()->timestamp)
                ->delete();
            
            $this->info("Removed {$inactiveSessions} inactive sessions older than 1 hour.");
            
            // Show current status
            $activeSessions = AdminSession::where('is_active', true)->count();
            $totalSessions = AdminSession::count();
            
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Active Sessions', $activeSessions],
                    ['Total Sessions', $totalSessions],
                ]
            );
        }
        
        return 0;
    }
}