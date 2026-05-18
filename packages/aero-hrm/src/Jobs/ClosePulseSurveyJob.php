<?php

declare(strict_types=1);

namespace Aero\HRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stub job — automatically close pulse surveys past their closes_at timestamp.
 * Full implementation pending scheduler integration.
 */
class ClosePulseSurveyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        // TODO: query hrm_pulse_surveys where status=active AND closes_at <= now(),
        // set status=closed
    }
}
