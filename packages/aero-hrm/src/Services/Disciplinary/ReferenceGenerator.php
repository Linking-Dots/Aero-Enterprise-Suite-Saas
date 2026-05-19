<?php

namespace Aero\HRM\Services\Disciplinary;

use Aero\HRM\Models\HrmDisciplinaryCase;
use Aero\HRM\Models\HrmGrievance;

final class ReferenceGenerator
{
    public function case(): string
    {
        $year = date('Y');
        $last = HrmDisciplinaryCase::whereYear('created_at', $year)->count();

        return sprintf('CASE-%s-%06d', $year, $last + 1);
    }

    public function grievance(): string
    {
        $year = date('Y');
        $last = HrmGrievance::whereYear('created_at', $year)->count();

        return sprintf('GRV-%s-%06d', $year, $last + 1);
    }
}
