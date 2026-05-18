<?php

declare(strict_types=1);

namespace Aero\HRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stub job — compute attrition risk scores for all active employees.
 * Full implementation pending ML/rule engine integration.
 */
class ComputeAttritionRiskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        // TODO: iterate active employees, call AttritionRiskService::score(),
        // upsert into hrm_attrition_risk_scores
    }
}
