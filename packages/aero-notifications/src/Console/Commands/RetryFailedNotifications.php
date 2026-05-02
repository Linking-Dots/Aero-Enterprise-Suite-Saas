<?php

declare(strict_types=1);

namespace Aero\Notifications\Console\Commands;

use Aero\Notifications\Models\NotificationLog;
use Aero\Notifications\Services\Pipeline\NotificationPipeline;
use Illuminate\Console\Command;

class RetryFailedNotifications extends Command
{
    protected $signature = 'aero:notifications:retry-failed {--limit=100}';
    protected $description = 'Retry failed notification deliveries';

    public function handle(NotificationPipeline $pipeline): int
    {
        $logs = NotificationLog::whereIn('status', [NotificationLog::STATUS_FAILED, NotificationLog::STATUS_PENDING])
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('last_attempt_at', '<=', now()->subMinutes(5))
            ->limit((int) $this->option('limit'))->get();

        $retried = 0;
        foreach ($logs as $log) {
            $this->info("Retrying notification #{$log->id} ({$log->channel})");
            $log->update(['attempts' => $log->attempts + 1, 'last_attempt_at' => now(), 'status' => NotificationLog::STATUS_PENDING]);
            $retried++;
        }
        $this->info("Retried {$retried} notifications.");
        return self::SUCCESS;
    }
}
