<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\TrainingEnrollment;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends SelfServiceController
{
    public function index(): Response
    {
        $employee = $this->employee();

        $enrollments = TrainingEnrollment::where('employee_id', $employee->id)
            ->with(['session.course.category', 'feedback'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('HRM/SelfService/Training', [
            'enrollments' => $enrollments,
        ]);
    }
}
