<?php

namespace Aero\HRM\Http\Controllers;

use Aero\HRM\Http\Requests\MarkWellbeingInterventionRequest;
use Aero\HRM\Models\AIInsight;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\EmployeeRiskScore;
use Aero\HRM\Services\AIAnalytics\BurnoutRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WellbeingController extends Controller
{
    public function __construct(private BurnoutRiskService $burnoutRiskService) {}

    public function index(): Response
    {
        try {
            $burnoutRisks = $this->burnoutRiskService->getHighRiskEmployees();

            $departmentRiskSummary = EmployeeRiskScore::query()
                ->with('employee.department')
                ->whereNotNull('burnout_risk_score')
                ->get()
                ->groupBy(fn (EmployeeRiskScore $risk) => $risk->employee?->department?->name ?? 'Unassigned')
                ->map(function (Collection $group, string $department): array {
                    return [
                        'department' => $department,
                        'employee_count' => $group->count(),
                        'average_risk' => round((float) $group->avg('burnout_risk_score'), 2),
                        'high_risk_count' => $group->where('burnout_risk_score', '>=', 60)->count(),
                    ];
                })
                ->values();

            // Axis C C1 — aggregate in SQL, not by loading the whole table into PHP.
            // Driver-aware month bucket (mysql/sqlite/pgsql) for the 6-month trend.
            $monthExpr = match (DB::connection()->getDriverName()) {
                'sqlite' => "strftime('%Y-%m', burnout_calculated_at)",
                'pgsql' => "to_char(burnout_calculated_at, 'YYYY-MM')",
                default => "DATE_FORMAT(burnout_calculated_at, '%Y-%m')",
            };

            $riskTrend = EmployeeRiskScore::query()
                ->whereNotNull('burnout_calculated_at')
                ->where('burnout_calculated_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("{$monthExpr} as month")
                ->selectRaw('ROUND(AVG(burnout_risk_score), 2) as average_risk')
                ->selectRaw('SUM(CASE WHEN burnout_risk_score >= 60 THEN 1 ELSE 0 END) as high_risk_count')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Single-row conditional aggregate instead of loading every score row.
            $stats = EmployeeRiskScore::query()
                ->whereNotNull('burnout_risk_score')
                ->selectRaw('COUNT(*) as total_monitored')
                ->selectRaw('SUM(CASE WHEN burnout_risk_score >= 60 THEN 1 ELSE 0 END) as high_risk')
                ->selectRaw('SUM(CASE WHEN burnout_risk_score >= 40 AND burnout_risk_score < 60 THEN 1 ELSE 0 END) as medium_risk')
                ->selectRaw('SUM(CASE WHEN burnout_risk_score < 40 THEN 1 ELSE 0 END) as low_risk')
                ->first();

            return Inertia::render('HRM/Wellbeing/Dashboard', [
                'title' => 'Wellbeing & Burnout Monitor',
                'burnoutRisks' => $burnoutRisks,
                'departmentRiskSummary' => $departmentRiskSummary,
                'riskTrend' => $riskTrend,
                'stats' => [
                    'high_risk' => (int) ($stats->high_risk ?? 0),
                    'medium_risk' => (int) ($stats->medium_risk ?? 0),
                    'low_risk' => (int) ($stats->low_risk ?? 0),
                    'total_monitored' => (int) ($stats->total_monitored ?? 0),
                ],
            ]);
        } catch (Throwable $exception) {
            return Inertia::render('HRM/Wellbeing/Dashboard', [
                'title' => 'Wellbeing & Burnout Monitor',
                'burnoutRisks' => [],
                'departmentRiskSummary' => [],
                'riskTrend' => [],
                'stats' => [
                    'high_risk' => 0,
                    'medium_risk' => 0,
                    'low_risk' => 0,
                    'total_monitored' => 0,
                ],
                'error' => 'Unable to load wellbeing data.',
            ]);
        }
    }

    public function employeeDetail(int $id): JsonResponse
    {
        try {
            $employee = Employee::query()->with(['department', 'designation', 'manager'])->findOrFail($id);
            $storedRisk = EmployeeRiskScore::query()->where('employee_id', $employee->id)->first();
            $liveRisk = $this->burnoutRiskService->calculateBurnoutRisk($employee);

            return response()->json([
                'data' => [
                    'employee' => $employee,
                    'storedRisk' => $storedRisk,
                    'liveRisk' => $liveRisk,
                ],
                'message' => 'Employee burnout profile loaded successfully.',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to load employee burnout profile.',
            ], 422);
        }
    }

    public function markIntervention(MarkWellbeingInterventionRequest $request, int $id): JsonResponse
    {
        try {
            $employee = Employee::query()->findOrFail($id);
            $validated = $request->validated();

            $note = trim((string) ($validated['note'] ?? $validated['notes'] ?? ''));
            $interventionType = $validated['intervention_type'] ?? null;
            $actionNote = $interventionType ? '['.$interventionType.'] '.$note : $note;

            $insight = AIInsight::query()
                ->where('insight_type', 'burnout_warning')
                ->where('employee_id', $employee->id)
                ->latest('id')
                ->first();

            if (! $insight) {
                $insight = AIInsight::query()->create([
                    'insight_type' => 'burnout_warning',
                    'severity' => 'medium',
                    'scope' => 'employee',
                    'employee_id' => $employee->id,
                    'department_id' => $employee->department_id,
                    'title' => 'Burnout intervention logged',
                    'description' => $actionNote,
                    'recommended_actions' => [$actionNote],
                    'confidence_score' => 50,
                    'status' => 'actioned',
                    'actioned_by' => auth()->id(),
                    'actioned_at' => now(),
                    'action_taken' => $actionNote,
                    'insight_date' => now()->toDateString(),
                    'valid_until' => $validated['follow_up_date'] ?? null,
                ]);
            } else {
                $insight->update([
                    'status' => 'actioned',
                    'actioned_by' => auth()->id(),
                    'actioned_at' => now(),
                    'action_taken' => $actionNote,
                    'valid_until' => $validated['follow_up_date'] ?? $insight->valid_until,
                ]);
            }

            return response()->json([
                'data' => $insight->fresh(),
                'message' => 'Intervention marked successfully.',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to mark intervention.',
            ], 422);
        }
    }
}
