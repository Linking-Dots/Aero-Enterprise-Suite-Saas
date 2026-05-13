<?php

namespace Aero\Compliance\Http\Controllers;

use Aero\Compliance\Models\AuditFinding;
use Aero\Compliance\Models\ComplianceAudit;
use Aero\Compliance\Models\CompliancePolicy;
use Aero\Compliance\Models\ComplianceTrainingRecord;
use Aero\Compliance\Models\ControlledDocument;
use Aero\Compliance\Models\RegulatoryRequirement;
use Aero\Compliance\Models\RiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class ComplianceController extends Controller
{
    /**
     * Display the compliance dashboard
     */
    public function index()
    {
        $stats = $this->getComplianceStats();

        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('Compliance/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $this->getRecentActivities(),
            'upcomingDeadlines' => $this->getUpcomingDeadlines(),
            'criticalIssues' => $this->getCriticalIssues(),
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }

    /**
     * Get compliance statistics for dashboard
     */
    private function getComplianceStats(): array
    {
        return [
            'policies' => [
                'total' => CompliancePolicy::count(),
                'active' => CompliancePolicy::active()->count(),
                'due_for_review' => CompliancePolicy::dueForReview()->count(),
                'overdue' => CompliancePolicy::overdue()->count(),
            ],
            'regulatory_requirements' => [
                'total' => RegulatoryRequirement::count(),
                'active' => RegulatoryRequirement::active()->count(),
                'compliance_rate' => $this->calculateComplianceRate(),
            ],
            'risk_assessments' => [
                'total' => RiskAssessment::count(),
                'high_risk' => RiskAssessment::highRisk()->count(),
                'overdue_review' => RiskAssessment::overdueReview()->count(),
                'active' => RiskAssessment::active()->count(),
            ],
            'audits' => [
                'total' => ComplianceAudit::count(),
                'active' => ComplianceAudit::active()->count(),
                'completed_this_year' => ComplianceAudit::completed()
                    ->whereYear('actual_date', now()->year)->count(),
                'findings_open' => AuditFinding::where('status', AuditFinding::STATUS_OPEN)->count(),
            ],
            'training' => [
                'total_records' => ComplianceTrainingRecord::count(),
                'completed_this_year' => ComplianceTrainingRecord::completed()
                    ->whereYear('completion_date', now()->year)->count(),
                'expired' => ComplianceTrainingRecord::expired()->count(),
                'expiring_soon' => ComplianceTrainingRecord::expiringSoon()->count(),
            ],
            'documents' => [
                'total' => ControlledDocument::count(),
                'active' => ControlledDocument::active()->count(),
                'due_for_review' => ControlledDocument::dueForReview()->count(),
                'pending_approval' => ControlledDocument::pendingApproval()->count(),
            ],
        ];
    }

    /**
     * Get recent compliance activities
     */
    private function getRecentActivities(): array
    {
        $activities = collect();

        // Recent policy updates
        $recentPolicies = CompliancePolicy::with('owner')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($policy) {
                return [
                    'type' => 'policy',
                    'title' => "Policy Updated: {$policy->title}",
                    'date' => $policy->updated_at,
                    'user' => $policy->owner->name ?? 'System',
                    'icon' => 'document-text',
                    'color' => 'blue',
                ];
            });

        // Recent audit findings
        $recentFindings = AuditFinding::with(['complianceAudit', 'responsiblePerson'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($finding) {
                return [
                    'type' => 'finding',
                    'title' => "New Finding: {$finding->title}",
                    'date' => $finding->created_at,
                    'user' => $finding->responsiblePerson->name ?? 'Unassigned',
                    'icon' => 'exclamation-triangle',
                    'color' => $finding->severity === 'critical' ? 'red' : 'yellow',
                ];
            });

        // Recent training completions
        $recentTraining = ComplianceTrainingRecord::with('employee')
            ->where('status', ComplianceTrainingRecord::STATUS_COMPLETED)
            ->orderBy('completion_date', 'desc')
            ->take(5)
            ->get()
            ->map(function ($training) {
                return [
                    'type' => 'training',
                    'title' => "Training Completed: {$training->training_title}",
                    'date' => $training->completion_date,
                    'user' => $training->employee->name ?? 'Unknown',
                    'icon' => 'academic-cap',
                    'color' => 'green',
                ];
            });

        return $activities
            ->merge($recentPolicies)
            ->merge($recentFindings)
            ->merge($recentTraining)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get upcoming deadlines
     */
    private function getUpcomingDeadlines(): array
    {
        $deadlines = collect();

        // Policy reviews
        $policyDeadlines = CompliancePolicy::dueForReview()
            ->take(5)
            ->get()
            ->map(function ($policy) {
                return [
                    'type' => 'Policy Review',
                    'title' => $policy->title,
                    'date' => $policy->next_review_date,
                    'days_until' => now()->diffInDays($policy->next_review_date, false),
                    'priority' => $policy->next_review_date < now() ? 'high' : 'medium',
                    'url' => route('compliance.policies.show', $policy->id),
                ];
            });

        // Training expirations
        $trainingDeadlines = ComplianceTrainingRecord::expiringSoon(30)
            ->with('employee')
            ->take(5)
            ->get()
            ->map(function ($training) {
                return [
                    'type' => 'Training Expiry',
                    'title' => "{$training->training_title} - {$training->employee->name}",
                    'date' => $training->expiry_date,
                    'days_until' => now()->diffInDays($training->expiry_date, false),
                    'priority' => $training->expiry_date <= now()->addDays(7) ? 'high' : 'medium',
                    'url' => route('compliance.training.show', $training->id),
                ];
            });

        // Document reviews
        $documentDeadlines = ControlledDocument::dueForReview()
            ->take(5)
            ->get()
            ->map(function ($document) {
                return [
                    'type' => 'Document Review',
                    'title' => $document->title,
                    'date' => $document->next_review_date,
                    'days_until' => now()->diffInDays($document->next_review_date, false),
                    'priority' => $document->next_review_date < now() ? 'high' : 'medium',
                    'url' => route('compliance.documents.show', $document->id),
                ];
            });

        return $deadlines
            ->merge($policyDeadlines)
            ->merge($trainingDeadlines)
            ->merge($documentDeadlines)
            ->sortBy('date')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get critical compliance issues
     */
    private function getCriticalIssues(): array
    {
        $issues = collect();

        // Critical audit findings
        $criticalFindings = AuditFinding::critical()
            ->where('status', AuditFinding::STATUS_OPEN)
            ->with(['complianceAudit', 'responsiblePerson'])
            ->get()
            ->map(function ($finding) {
                return [
                    'type' => 'Critical Finding',
                    'title' => $finding->title,
                    'description' => $finding->description,
                    'responsible' => $finding->responsiblePerson->name ?? 'Unassigned',
                    'due_date' => $finding->due_date,
                    'audit' => $finding->complianceAudit->title,
                    'priority' => 'critical',
                    'url' => route('compliance.findings.show', $finding->id),
                ];
            });

        // High risk assessments
        $highRisks = RiskAssessment::highRisk()
            ->where('status', RiskAssessment::STATUS_PENDING)
            ->with('owner')
            ->get()
            ->map(function ($risk) {
                return [
                    'type' => 'High Risk',
                    'title' => $risk->name,
                    'description' => $risk->description,
                    'responsible' => $risk->owner->name ?? 'Unassigned',
                    'risk_level' => $risk->risk_level,
                    'priority' => $risk->risk_level === 'critical' ? 'critical' : 'high',
                    'url' => route('compliance.risks.show', $risk->id),
                ];
            });

        // Overdue policies
        $overduePolicies = CompliancePolicy::overdue()
            ->with('owner')
            ->get()
            ->map(function ($policy) {
                return [
                    'type' => 'Overdue Policy',
                    'title' => $policy->title,
                    'description' => 'Policy review is overdue',
                    'responsible' => $policy->owner->name ?? 'Unassigned',
                    'due_date' => $policy->next_review_date,
                    'priority' => 'high',
                    'url' => route('compliance.policies.show', $policy->id),
                ];
            });

        return $issues
            ->merge($criticalFindings)
            ->merge($highRisks)
            ->merge($overduePolicies)
            ->sortByDesc('priority')
            ->take(15)
            ->values()
            ->toArray();
    }

    /**
     * Calculate overall compliance rate
     */
    private function calculateComplianceRate(): float
    {
        $totalRequirements = RegulatoryRequirement::active()->count();
        if ($totalRequirements === 0) {
            return 100.0;
        }

        $compliantRequirements = RegulatoryRequirement::active()
            ->where('compliance_status', RegulatoryRequirement::STATUS_COMPLIANT)
            ->count();

        return round(($compliantRequirements / $totalRequirements) * 100, 2);
    }

    /**
     * Export compliance report
     */
    public function exportReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:overview,policies,audits,risks,training,documents',
            'format' => 'required|in:pdf,excel',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Implementation for report generation would go here
        // This would typically use a service class or queue job

        return response()->json([
            'message' => 'Report generation started',
            'status' => 'processing',
        ]);
    }

    /**
     * Get compliance metrics for analytics
     */
    public function getMetrics(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);

        return response()->json([
            'compliance_trend' => $this->getComplianceTrend($startDate),
            'risk_distribution' => $this->getRiskDistribution(),
            'audit_performance' => $this->getAuditPerformance($startDate),
            'training_completion' => $this->getTrainingCompletion($startDate),
        ]);
    }

    /**
     * Get compliance trend over time
     */
    private function getComplianceTrend($startDate): array
    {
        // Implementation for compliance trend calculation
        return [];
    }

    /**
     * Get risk distribution by category and level
     */
    private function getRiskDistribution(): array
    {
        return [
            'by_level' => RiskAssessment::selectRaw('risk_level, COUNT(*) as count')
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level')
                ->toArray(),
            'by_category' => RiskAssessment::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
        ];
    }

    /**
     * Get audit performance metrics
     */
    private function getAuditPerformance($startDate): array
    {
        return [
            'completed_on_time' => ComplianceAudit::where('end_date', '>=', $startDate)
                ->whereColumn('end_date', '<=', 'scheduled_date')
                ->count(),
            'total_completed' => ComplianceAudit::where('end_date', '>=', $startDate)->count(),
            'findings_by_severity' => AuditFinding::whereHas('complianceAudit', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
        ];
    }

    /**
     * Get training completion metrics
     */
    private function getTrainingCompletion($startDate): array
    {
        return [
            'completed' => ComplianceTrainingRecord::where('completion_date', '>=', $startDate)
                ->where('status', ComplianceTrainingRecord::STATUS_COMPLETED)
                ->count(),
            'failed' => ComplianceTrainingRecord::where('completion_date', '>=', $startDate)
                ->where('status', ComplianceTrainingRecord::STATUS_FAILED)
                ->count(),
            'by_category' => ComplianceTrainingRecord::where('completion_date', '>=', $startDate)
                ->where('status', ComplianceTrainingRecord::STATUS_COMPLETED)
                ->selectRaw('training_category, COUNT(*) as count')
                ->groupBy('training_category')
                ->pluck('count', 'training_category')
                ->toArray(),
        ];
    }
}
