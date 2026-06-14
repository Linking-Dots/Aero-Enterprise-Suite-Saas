<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\NewsletterSubscriber;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Marketing\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Newsletter Controller
 *
 * Manages newsletter subscribers from the platform admin.
 */
class NewsletterController extends Controller
{
    public function __construct(
        protected NewsletterService $newsletterService
    ) {}

    /**
     * Display subscribers list.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'source', 'preference', 'sort_by', 'sort_dir']);
        $perPage = $request->input('perPage', 20);

        $subscribers = $this->newsletterService->getPaginatedSubscribers($filters, $perPage);
        $stats = $this->newsletterService->getSubscriberStats();
        $settings = PlatformSetting::current()->getNewsletterSettings();

        return Inertia::render('Platform/Admin/Newsletter/Index', [
            'title' => 'Newsletter Subscribers',
            'subscribers' => $subscribers,
            'stats' => $stats,
            'filters' => $filters,
            'settings' => $settings,
            'statusOptions' => NewsletterSubscriber::getStatusOptions(),
            'sourceOptions' => NewsletterSubscriber::getSourceOptions(),
        ]);
    }

    /**
     * Get paginated subscribers (API).
     */
    public function paginate(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'source', 'preference', 'sort_by', 'sort_dir']);
        $perPage = $request->input('perPage', 20);

        $subscribers = $this->newsletterService->getPaginatedSubscribers($filters, $perPage);

        return response()->json($subscribers);
    }

    /**
     * Show subscriber details.
     */
    public function show(NewsletterSubscriber $subscriber): Response
    {
        return Inertia::render('Platform/Admin/Newsletter/Show', [
            'title' => 'Subscriber Details',
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Manually add a subscriber.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:'.implode(',', array_keys(NewsletterSubscriber::getSourceOptions())),
            'preferences' => 'nullable|array',
            'skip_confirmation' => 'boolean',
        ]);

        $subscriber = $this->newsletterService->subscribe(
            $validated['email'],
            $validated['name'] ?? null,
            $validated['source'] ?? NewsletterSubscriber::SOURCE_MANUAL,
            $validated['preferences'] ?? []
        );

        if ($validated['skip_confirmation'] ?? false) {
            $subscriber->confirm();
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscriber added successfully.',
            'data' => $subscriber,
        ], 201);
    }

    /**
     * Update subscriber.
     */
    public function update(Request $request, NewsletterSubscriber $subscriber): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'preferences' => 'nullable|array',
            'status' => 'nullable|string|in:'.implode(',', array_keys(NewsletterSubscriber::getStatusOptions())),
        ]);

        $subscriber->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber updated successfully.',
            'data' => $subscriber->fresh(),
        ]);
    }

    /**
     * Delete subscriber.
     */
    public function destroy(NewsletterSubscriber $subscriber): JsonResponse
    {
        $subscriber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscriber deleted successfully.',
        ]);
    }

    /**
     * Resend confirmation email.
     */
    public function resendConfirmation(NewsletterSubscriber $subscriber): JsonResponse
    {
        if ($subscriber->isConfirmed()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber is already confirmed.',
            ], 400);
        }

        $this->newsletterService->sendConfirmationEmail($subscriber);

        return response()->json([
            'success' => true,
            'message' => 'Confirmation email sent successfully.',
        ]);
    }

    /**
     * Manually confirm subscriber.
     */
    public function confirm(NewsletterSubscriber $subscriber): JsonResponse
    {
        if ($subscriber->isConfirmed()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber is already confirmed.',
            ], 400);
        }

        $subscriber->confirm();

        return response()->json([
            'success' => true,
            'message' => 'Subscriber confirmed successfully.',
            'data' => $subscriber->fresh(),
        ]);
    }

    /**
     * Unsubscribe manually.
     */
    public function unsubscribe(Request $request, NewsletterSubscriber $subscriber): JsonResponse
    {
        $reason = $request->input('reason');
        $subscriber->unsubscribe($reason);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber unsubscribed successfully.',
            'data' => $subscriber->fresh(),
        ]);
    }

    /**
     * Bulk delete subscribers.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $count = NewsletterSubscriber::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} subscribers deleted successfully.",
        ]);
    }

    /**
     * Get subscriber statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->newsletterService->getSubscriberStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Import subscribers.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscribers' => 'required|array|min:1',
            'subscribers.*.email' => 'required|email',
            'subscribers.*.name' => 'nullable|string|max:255',
            'subscribers.*.preferences' => 'nullable|array',
            'skip_confirmation' => 'boolean',
        ]);

        $result = $this->newsletterService->importSubscribers(
            $validated['subscribers'],
            $validated['skip_confirmation'] ?? false
        );

        return response()->json([
            'success' => true,
            'message' => "{$result['imported']} subscribers imported, {$result['skipped']} skipped.",
            'result' => $result,
        ]);
    }

    /**
     * Export subscribers.
     */
    public function export(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $subscribers = $this->newsletterService->exportSubscribers($status);

        return response()->json([
            'success' => true,
            'data' => $subscribers,
        ]);
    }

    /**
     * Update newsletter settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'require_confirmation' => 'boolean',
            'welcome_email_enabled' => 'boolean',
            'welcome_email_subject' => 'nullable|string|max:255',
            'unsubscribe_feedback' => 'boolean',
            'mailchimp_api_key' => 'nullable|string|max:255',
            'mailchimp_list_id' => 'nullable|string|max:100',
        ]);

        $settings = PlatformSetting::current();
        $newsletterSettings = array_merge($settings->newsletter_settings ?? [], $validated);
        $settings->update(['newsletter_settings' => $newsletterSettings]);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter settings updated successfully.',
            'data' => $settings->fresh()->getNewsletterSettings(),
        ]);
    }
}
