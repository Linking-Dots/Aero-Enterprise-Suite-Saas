<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\Goal;
use Aero\HRM\Models\HrmPerformanceReview;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends SelfServiceController
{
    public function index(): Response
    {
        $employee = $this->employee();

        $goals = Goal::where('employee_id', $employee->id)
            ->orderByDesc('time_bound')
            ->get();

        $reviews = HrmPerformanceReview::where('employee_id', $employee->id)
            ->with(['cycle:id,name'])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('HRM/SelfService/Performance', [
            'goals' => $goals,
            'reviews' => $reviews,
        ]);
    }
}
