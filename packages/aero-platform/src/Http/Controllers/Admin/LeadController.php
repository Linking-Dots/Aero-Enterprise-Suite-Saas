<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\ProspectLead;
use Aero\Platform\Services\Marketing\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lead Controller
 *
 * Manages prospect leads from the platform admin.
 */
class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Leads command centre — the /leads landing (full CRM console).
     */
    public function overview(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Leads/P2/Leads', [
            'overview' => fn () => $this->leadService->overview($request->input('period', '6m')),
        ]);
    }

    /**
     * Legacy leads list — subsumed by the command centre; redirect.
     */
    public function index(): RedirectResponse
    {
        return redirect('/leads');
    }

    /**
     * Get paginated leads (API).
     */
    public function paginate(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'status', 'source', 'assigned_to', 'unassigned',
            'min_score', 'date_from', 'date_to', 'sort_by', 'sort_dir',
        ]);

        $perPage = $request->input('perPage', 20);
        $leads = $this->leadService->getPaginatedLeads($filters, $perPage);

        return response()->json($leads);
    }

    /**
     * Lead detail — subsumed by the console drawer; redirect to the console.
     */
    public function show(ProspectLead $lead): RedirectResponse
    {
        return redirect('/leads');
    }

    /**
     * Store a new lead.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'source' => 'required|string|in:'.implode(',', array_keys(ProspectLead::getSourceOptions())),
            'source_detail' => 'nullable|string|max:500',
            'interest_level' => 'nullable|string|in:low,medium,high',
            'interests' => 'nullable|array',
            'notes' => 'nullable|string|max:5000',
        ]);

        $lead = $this->leadService->createLead($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => $lead,
        ], 201);
    }

    /**
     * Update a lead.
     */
    public function update(Request $request, ProspectLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'source' => 'nullable|string|in:'.implode(',', array_keys(ProspectLead::getSourceOptions())),
            'source_detail' => 'nullable|string|max:500',
            'interest_level' => 'nullable|string|in:low,medium,high',
            'interests' => 'nullable|array',
            'notes' => 'nullable|string|max:5000',
            'status' => 'nullable|string|in:'.implode(',', array_keys(ProspectLead::getStatusOptions())),
        ]);

        $lead = $this->leadService->updateLead($lead, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'data' => $lead,
        ]);
    }

    /**
     * Delete a lead.
     */
    public function destroy(ProspectLead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }

    /**
     * Assign lead to user.
     */
    public function assign(Request $request, ProspectLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:central.users,id',
        ]);

        $this->leadService->assignLead($lead, $validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'Lead assigned successfully.',
            'data' => $lead->fresh('assignee'),
        ]);
    }

    /**
     * Bulk assign leads.
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:prospect_leads,id',
            'user_id' => 'required|integer|exists:central.users,id',
        ]);

        $count = $this->leadService->bulkAssignLeads($validated['lead_ids'], $validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => "{$count} leads assigned successfully.",
        ]);
    }

    /**
     * Move a lead to a new pipeline stage (sets the matching stage timestamp).
     */
    public function updateStatus(Request $request, ProspectLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(ProspectLead::getStatusOptions())),
            'reason' => 'nullable|string|max:500',
        ]);

        $this->leadService->transition($lead, $validated['status'], $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully.',
            'data' => $lead->fresh('assignee'),
        ]);
    }

    /**
     * Bulk lifecycle action (assign / move stage / mark lost / delete).
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:assign,contacted,qualified,lost,delete',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:prospect_leads,id',
            'user_id' => 'nullable|integer|exists:central.users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $count = $this->leadService->bulkAction(
            $validated['lead_ids'],
            $validated['action'],
            $validated['user_id'] ?? null,
            $validated['reason'] ?? null,
        );

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "{$count} lead(s) updated.",
        ]);
    }

    /**
     * Convert lead to tenant.
     */
    public function convert(Request $request, ProspectLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $this->leadService->convertLead($lead, $validated['tenant_id']);

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to tenant successfully.',
            'data' => $lead->fresh('tenant'),
        ]);
    }

    /**
     * Get lead statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $stats = $this->leadService->getLeadStats($period);
        $funnel = $this->leadService->getLeadFunnel($period);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'funnel' => $funnel,
        ]);
    }

    /**
     * Get high-value leads.
     */
    public function highValue(Request $request): JsonResponse
    {
        $minScore = $request->input('min_score', 70);
        $limit = $request->input('limit', 10);

        $leads = $this->leadService->getHighValueLeads($minScore, $limit);

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }
}
