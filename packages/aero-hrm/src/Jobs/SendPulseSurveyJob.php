<?php

declare(strict_types=1);

namespace Aero\HRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stub job — send a pulse survey to eligible employees via notification.
 * Full implementation pending notification channel setup.
 */
class SendPulseSurveyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        // TODO: load eligible employees based on audience_filter,
        // dispatch individual survey invitation notifications
    }
}
