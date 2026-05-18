<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Inertia\Inertia;
use Inertia\Response;

class SkillMatrixController extends Controller
{
    /**
     * Display the skill matrix for all employees.
     * Gate: hrm.performance.skill-matrix.view
     */
    public function matrix(): Response
    {
        $this->authorize('hrm.performance.skill-matrix.view');

        // Load employees with skills if the relationship exists; fall back to empty collection
        try {
            $employees = Employee::with('skills')
                ->orderBy('first_name')
                ->get();
        } catch (\Exception) {
            $employees = Employee::orderBy('first_name')->get()->each(function (Employee $e): void {
                $e->setRelation('skills', collect());
            });
        }

        return Inertia::render('HRM/Performance/Skills/Matrix', [
            'employees' => $employees,
        ]);
    }
}
