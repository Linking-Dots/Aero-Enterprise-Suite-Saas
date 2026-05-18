<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Analytics;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Services\Analytics\DEIService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DEIController extends Controller
{
    public function __construct(
        private readonly DEIService $dei,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * DEI Analytics dashboard.
     */
    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.workforce-planning.dei-analytics.view');

        $this->audit->log(
            event: 'analytics.dei.view',
            action: 'view',
            subject: null,
            description: 'DEI Analytics dashboard accessed',
        );

        return Inertia::render('HRM/Analytics/DEI/Index', [
            'genderDistribution' => $this->dei->genderDistribution(),
            'ageBands' => $this->dei->ageBands(),
            'payGapByRoleBand' => $this->dei->payGapByRoleBand(),
            'leadershipRepresentation' => $this->dei->leadershipRepresentation(),
        ]);
    }
}
