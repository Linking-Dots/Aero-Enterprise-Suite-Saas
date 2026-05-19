<?php

namespace Aero\HRM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BenefitElectionsCommitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $employeeId,
        public readonly int $periodId,
    ) {}
}
