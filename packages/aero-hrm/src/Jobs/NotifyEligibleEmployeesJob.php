<?php

namespace Aero\HRM\Jobs;

use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class NotifyEligibleEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly HrmBenefitEnrollmentPeriod $period) {}

    public function handle(): void
    {
        // TODO H-11.1: Notify eligible employees about open enrollment period
    }
}
