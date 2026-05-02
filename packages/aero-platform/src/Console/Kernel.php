<?php

namespace Aero\Platform\Console;

use App\Console\Commands\SendAttendanceReminders;
use App\Models\NotificationLog;
use Aero\Platform\Console\Commands\ExpireGracePeriods;
use Aero\Platform\Console\Commands\ProcessPendingSubscriptionChanges;
use Aero\Platform\Console\Commands\ProcessSubscriptionRenewals;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendAttendanceReminders::class,
        ProcessSubscriptionRenewals::class,
        ExpireGracePeriods::class,
        ProcessPendingSubscriptionChanges::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Test scheduler command runs every minute (for testing only)
        if (config('app.env') === 'local') {
            $schedule->command('test:scheduler')
                ->everyMinute()
                ->onFailure(function () {
                    Log::error('Test scheduler failed');
                });
        }

        $schedule->command('queue:work --stop-when-empty --tries=3')->everyMinute();
        // Send attendance reminders daily at 8:00 AM
        $schedule->command('attendance:reminders')
            ->dailyAt('22:17')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting attendance reminder job');
            })
            ->onSuccess(function () {
                Log::info('Attendance reminders sent successfully');
            })
            ->onFailure(function () {
                Log::error('Failed to send attendance reminders');
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/attendance-reminders.log'));

        // Auto-mark absences daily at 11:00 PM for previous day
        $schedule->command('attendance:auto-mark-absences')
            ->dailyAt('23:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting auto-mark absences job');
            })
            ->onSuccess(function () {
                Log::info('Auto-mark absences completed successfully');
            })
            ->onFailure(function () {
                Log::error('Auto-mark absences failed');
            })
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/auto-mark-absences.log'));

        // Clean up old notification logs (keep 30 days)
        $schedule->command('model:prune', [
            '--model' => [
                NotificationLog::class,
            ],
        ])->daily();

        // Clean up abandoned pending registrations (older than 24 hours)
        $schedule->command('registrations:cleanup --hours=24')
            ->hourly()
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting abandoned registrations cleanup');
            })
            ->onSuccess(function () {
                Log::info('Abandoned registrations cleanup completed');
            })
            ->onFailure(function () {
                Log::error('Abandoned registrations cleanup failed');
            })
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/registration-cleanup.log'));

        // Clean up failed tenant provisioning attempts (keep 7 days)
        $schedule->command('tenants:cleanup-failed')
            ->dailyAt('02:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting failed tenants cleanup');
            })
            ->onSuccess(function () {
                Log::info('Failed tenants cleanup completed');
            })
            ->onFailure(function () {
                Log::error('Failed tenants cleanup failed');
            })
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/tenant-cleanup.log'));

        // Leave management scheduled tasks
        // Process leave carry forward - runs on January 1st at midnight
        $schedule->command('leave:process-carry-forward')
            ->yearly()
            ->timezone(config('app.timezone', 'UTC'))
            ->at('00:00')
            ->before(function () {
                Log::info('Starting leave carry forward process');
            })
            ->onSuccess(function () {
                Log::info('Leave carry forward completed successfully');
            })
            ->onFailure(function () {
                Log::error('Leave carry forward failed');
            })
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/leave-carry-forward.log'));

        // Process monthly leave accrual - runs on 1st of each month at midnight
        $schedule->command('leave:process-monthly-accrual')
            ->monthlyOn(1, '00:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting monthly leave accrual process');
            })
            ->onSuccess(function () {
                Log::info('Monthly leave accrual completed successfully');
            })
            ->onFailure(function () {
                Log::error('Monthly leave accrual failed');
            })
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/leave-accrual.log'));

        // Aggregate tenant statistics - runs daily at 11:55 PM
        $schedule->command('stats:aggregate')
            ->dailyAt('23:55')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting tenant stats aggregation');
            })
            ->onSuccess(function () {
                Log::info('Tenant stats aggregation completed successfully');
            })
            ->onFailure(function () {
                Log::error('Tenant stats aggregation failed');
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/tenant-stats.log'));

        // Subscription lifecycle scheduled tasks
        $schedule->command('subscriptions:process-renewals')
            ->dailyAt('01:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting subscription renewal processing');
            })
            ->onSuccess(function () {
                Log::info('Subscription renewals processed successfully');
            })
            ->onFailure(function () {
                Log::error('Subscription renewal processing failed');
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/subscription-renewals.log'));

        $schedule->command('subscriptions:expire-grace-periods')
            ->dailyAt('01:30')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting grace period expiration');
            })
            ->onSuccess(function () {
                Log::info('Grace period expiration completed successfully');
            })
            ->onFailure(function () {
                Log::error('Grace period expiration failed');
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/grace-periods.log'));

        $schedule->command('subscriptions:process-pending')
            ->dailyAt('02:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->before(function () {
                Log::info('Starting pending subscription changes processing');
            })
            ->onSuccess(function () {
                Log::info('Pending subscription changes processed successfully');
            })
            ->onFailure(function () {
                Log::error('Pending subscription changes processing failed');
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/pending-subscription-changes.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
