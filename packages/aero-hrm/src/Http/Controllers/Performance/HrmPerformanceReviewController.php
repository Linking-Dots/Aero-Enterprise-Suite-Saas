<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Services\Performance\ReviewSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrmPerformanceReviewController extends Controller
{
    /**
     * List performance reviews.
     * Gate: hrm.performance.reviews-360.view
     */
    public function index(Request $request): Response
    {
        $this->authorize('hrm.performance.reviews-360.view');

        $reviews = HrmPerformanceReview::with(['cycle', 'employee', 'manager'])
            ->when($request->status, function ($q, string $status): void {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Performance/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['status', 'per_page']),
        ]);
    }

    /**
     * Show a single performance review.
     * Gate: hrm.performance.reviews-360.view
     */
    public function show(HrmPerformanceReview $review): Response
    {
        $this->authorize('hrm.performance.reviews-360.view');

        $review->load(['cycle.template', 'employee', 'manager', 'finalizedBy']);

        return Inertia::render('HRM/Performance/Reviews/Show', [
            'review' => $review,
        ]);
    }

    /**
     * Submit self-assessment answers.
     * Gate: hrm.performance.reviews-360.submit
     */
    public function submitSelf(Request $request, HrmPerformanceReview $review, ReviewSubmissionService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.reviews-360.submit');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $svc->submitSelf($review, $validated['answers']);

        return back()->with('success', 'Self-assessment submitted.');
    }

    /**
     * Submit manager review answers.
     * Gate: hrm.performance.reviews-360.approve
     */
    public function submitManager(Request $request, HrmPerformanceReview $review, ReviewSubmissionService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.reviews-360.approve');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $svc->submitManager($review, $validated['answers']);

        return back()->with('success', 'Manager review submitted.');
    }

    /**
     * Finalize a performance review with a rating.
     * Gate: hrm.performance.reviews-360.approve
     */
    public function finalize(Request $request, HrmPerformanceReview $review, ReviewSubmissionService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.reviews-360.approve');

        $validated = $request->validate([
            'final_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'final_comment' => ['required', 'string', 'max:2000'],
        ]);

        $svc->finalize($review, (float) $validated['final_rating'], $validated['final_comment']);

        return back()->with('success', 'Review finalized.');
    }
}
