<?php

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Events\Performance\PerformanceReviewCompleted;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\PerformanceReview;
use Aero\HRM\Models\PerformanceReviewTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PerformanceReviewController extends Controller
{
    /**
     * Display the HR dashboard.
     */
    public function dashboard()
    {
        // Get statistics for the dashboard
        $totalReviews = PerformanceReview::count();
        $pendingReviews = PerformanceReview::whereIn('status', ['self_assessment_pending', 'manager_review_pending'])->count();
        $completedReviews = PerformanceReview::where('status', 'completed')->count();
        $averageRating = PerformanceReview::whereNotNull('overall_rating')->avg('overall_rating');

        // Recent reviews
        $recentReviews = PerformanceReview::with(['employee', 'reviewer'])
            ->latest()
            ->take(5)
            ->get();

        // Upcoming reviews
        $upcomingReviews = PerformanceReview::with(['employee', 'reviewer'])
            ->where('status', 'scheduled')
            ->orderBy('review_period_start')
            ->take(5)
            ->get();

        return Inertia::render('HRM/Dashboard', [
            'stats' => [
                'totalReviews' => $totalReviews,
                'pendingReviews' => $pendingReviews,
                'completedReviews' => $completedReviews,
                'averageRating' => $averageRating,
            ],
            'recentReviews' => $recentReviews,
            'upcomingReviews' => $upcomingReviews,
        ]);
    }

    /**
     * Display a listing of performance reviews.
     */
    public function index(Request $request)
    {
        $reviews = PerformanceReview::with(['employee', 'reviewer', 'department'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('reviewer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->department_id, function ($query, $departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->orderBy($request->input('sort_by', 'review_period_start'), $request->input('sort_order', 'desc'))
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        // If this is an AJAX request, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'data' => $reviews->items(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'from' => $reviews->firstItem(),
                'to' => $reviews->lastItem(),
                'prev_page_url' => $reviews->previousPageUrl(),
                'next_page_url' => $reviews->nextPageUrl(),
            ]);
        }

        return Inertia::render('HRM/Performance/Index', [
            'title' => 'Performance Reviews',
            'reviews' => $reviews,
            'filters' => $request->only(['search', 'status', 'department_id', 'sort_by', 'sort_order']),
            'departments' => Department::select('id', 'name')->get(),
            'employees' => \Aero\Core\Models\User::all(['id', 'name']),
            'templates' => PerformanceReviewTemplate::where('is_active', true)->get(['id', 'name']),
            'statuses' => [
                ['id' => 'scheduled', 'name' => 'Scheduled'],
                ['id' => 'in_progress', 'name' => 'In Progress'],
                ['id' => 'pending_acknowledgment', 'name' => 'Pending Acknowledgment'],
                ['id' => 'completed', 'name' => 'Completed'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Get performance review statistics.
     */
    public function stats()
    {
        $stats = [
            'total' => PerformanceReview::count(),
            'pending' => PerformanceReview::where('status', 'scheduled')->count(),
            'in_progress' => PerformanceReview::where('status', 'in_progress')->count(),
            'completed' => PerformanceReview::where('status', 'completed')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Show the form for creating a new performance review.
     */
    public function create()
    {
        return Inertia::render('HRM/Performance/Create', [
            'employees' => \Aero\Core\Models\User::all(['id', 'name']),
            'reviewers' => $this->getUsersWithPerformanceAccess(),
            'departments' => Department::all(['id', 'name']),
            'templates' => PerformanceReviewTemplate::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created performance review in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after:review_period_start',
            'review_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'template_id' => 'required|exists:performance_review_templates,id',
            'status' => 'required|in:scheduled,in_progress,pending_acknowledgment,completed,cancelled',
        ]);

        $review = PerformanceReview::create($validated);

        return redirect()->route('hrm.performance.show', $review->id)
            ->with('success', 'Performance review scheduled successfully.');
    }

    /**
     * Display the specified performance review.
     */
    public function show($id)
    {
        $review = PerformanceReview::with([
            'employee',
            'reviewer',
            'department',
            'template',
        ])->findOrFail($id);

        return Inertia::render('HRM/Performance/Show', [
            'review' => $review,
        ]);
    }

    /**
     * Show the form for editing the specified performance review.
     */
    public function edit($id)
    {
        $review = PerformanceReview::with([
            'employee',
            'reviewer',
            'department',
            'template',
        ])->findOrFail($id);

        return Inertia::render('HRM/Performance/Edit', [
            'review' => $review,
            'employees' => \Aero\Core\Models\User::all(['id', 'name']),
            'reviewers' => $this->getUsersWithPerformanceAccess(),
            'departments' => Department::all(['id', 'name']),
            'templates' => PerformanceReviewTemplate::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified performance review in storage.
     */
    public function update(Request $request, $id)
    {
        $review = PerformanceReview::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after:review_period_start',
            'review_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'template_id' => 'required|exists:performance_review_templates,id',
            'status' => 'required|in:scheduled,in_progress,pending_acknowledgment,completed,cancelled',
            'overall_rating' => 'nullable|numeric|min:1|max:5',
            'goals_achieved' => 'nullable|string',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'comments' => 'nullable|string',
            'employee_comments' => 'nullable|string',
            'next_review_date' => 'nullable|date',
        ]);

        $review->update($validated);

        // Dispatch PerformanceReviewCompleted event if status is completed
        if ($validated['status'] === 'completed' && isset($validated['overall_rating'])) {
            event(new PerformanceReviewCompleted(
                $review,
                $validated['overall_rating'],
                $validated['comments'] ?? ''
            ));
        }

        return redirect()->route('hrm.performance.show', $review->id)
            ->with('success', 'Performance review updated successfully.');
    }

    /**
     * Remove the specified performance review from storage.
     */
    public function destroy($id)
    {
        $review = PerformanceReview::findOrFail($id);
        $review->delete();

        return redirect()->route('hrm.performance.index')
            ->with('success', 'Performance review deleted successfully.');
    }

    /**
     * Display a listing of performance review templates.
     */
    public function templates()
    {
        $templates = PerformanceReviewTemplate::with('creator')
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('HRM/Performance/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new performance review template.
     */
    public function createTemplate()
    {
        return Inertia::render('HRM/Performance/Templates/Create', [
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created performance review template in storage.
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive',
            'default_for_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();

        $template = PerformanceReviewTemplate::create($validated);

        return redirect()->route('hrm.performance.templates.show', $template->id)
            ->with('success', 'Performance review template created successfully.');
    }

    /**
     * Display the specified performance review template.
     */
    public function showTemplate($id)
    {
        $template = PerformanceReviewTemplate::with([
            'creator',
            'defaultForDepartment',
            'competencyCategories.competencies',
        ])->findOrFail($id);

        return Inertia::render('HRM/Performance/Templates/Show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified performance review template.
     */
    public function editTemplate($id)
    {
        $template = PerformanceReviewTemplate::with([
            'creator',
            'defaultForDepartment',
            'competencyCategories.competencies',
        ])->findOrFail($id);

        return Inertia::render('HRM/Performance/Templates/Edit', [
            'template' => $template,
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified performance review template in storage.
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = PerformanceReviewTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive',
            'default_for_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('hrm.performance.templates.show', $template->id)
            ->with('success', 'Performance review template updated successfully.');
    }

    /**
     * Remove the specified performance review template from storage.
     */
    public function destroyTemplate($id)
    {
        $template = PerformanceReviewTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('hrm.performance.templates.index')
            ->with('success', 'Performance review template deleted successfully.');
    }

    /**
     * Get users with performance module access for reviewer dropdowns.
     */
    protected function getUsersWithPerformanceAccess(): \Illuminate\Support\Collection
    {
        try {
            return \Aero\HRMAC\Facades\HRMAC::getUsersWithSubModuleAccess('hrm', 'performance')
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]);
        } catch (\Exception $e) {
            // Fallback to all active users
            return \Aero\Core\Models\User::active()
                ->get(['id', 'name']);
        }
    }
}
