<?php

use App\Jobs\ExpireUsersJob;
use App\Jobs\SendExpirationReminderJob;
use App\Jobs\CleanupExpiredSessions;
use App\Jobs\CleanupOldAuditLogs;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of the scheduled tasks for your application.
|
*/

// Check for expired users every hour
Schedule::job(new ExpireUsersJob)->hourly();

// Send expiration reminders daily at 9 AM
Schedule::job(new SendExpirationReminderJob)->dailyAt('09:00');

// Cleanup expired sessions every 6 hours
Schedule::job(new CleanupExpiredSessions)->everySixHours();

// Cleanup old audit logs daily at 2 AM
Schedule::job(new CleanupOldAuditLogs)->dailyAt('02:00');

// Sync pending users with FortiGate every 30 minutes
Schedule::command('users:sync-fortigate')->everyThirtyMinutes();
