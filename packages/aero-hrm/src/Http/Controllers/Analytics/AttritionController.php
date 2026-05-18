<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Analytics;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\AttritionRiskScore;
use Aero\HRM\Services\Analytics\AttritionRiskService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttritionController extends Controller
{
    public function __construct(
        private readonly AttritionRiskService $riskService,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Attrition risk predictions dashboard.
     */
    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.ai-analytics.attrition-predictions.view');

        // Retrieve pre-computed scores, highest risk first
        $scores = AttritionRiskScore::with(['employee:id,user_id', 'employee.user:id,name'])
            ->orderByDesc('score')
            ->paginate(25)
            ->withQueryString();

        $this->audit->log(
            event: 'analytics.attrition.view',
            action: 'view',
            subject: null,
            description: 'Attrition risk predictions page accessed',
        );

        return Inertia::render('HRM/Analytics/Attrition/Index', [
            'risks' => $scores,
            'bands' => ['low', 'medium', 'high', 'critical'],
        ]);
    }
}
