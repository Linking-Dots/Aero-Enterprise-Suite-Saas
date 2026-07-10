<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\NewsletterCampaign;
use Aero\Platform\Models\NewsletterSubscriber;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Marketing\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     * Newsletter command centre — the /newsletter landing (audience + campaigns).
     */
    public function overview(): Response
    {
        return Inertia::render('Platform/Admin/Newsletter/P2/Newsletter', [
            'overview' => fn () => $this->newsletterService->overview(),
        ]);
    }

    /**
     * Legacy subscribers list — subsumed by the command centre; redirect.
     */
    public function index(): RedirectResponse
    {
        return redirect('/newsletter');
    }

    /**
     * Get paginated subscribers (API).
     */
    public function paginate(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'source', 'preference', 'sort_by', 'sort_dir']);
        $perPage = (int) $request->input('perPage', 20);

        $subscribers = $this->newsletterService->getPaginatedSubscribers($filters, $perPage);

        return response()->json($subscribers);
    }

    /**
     * Subscriber detail — subsumed by the console drawer; redirect.
     */
    public function show(NewsletterSubscriber $subscriber): RedirectResponse
    {
        return redirect('/newsletter');
    }

    /**
     * Bulk subscriber action (confirm / unsubscribe / delete).
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:confirm,unsubscribe,delete',
            'subscriber_ids' => 'required|array|min:1',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $count = $this->newsletterService->bulkSubscriberAction($validated['subscriber_ids'], $validated['action']);

        return response()->json(['success' => true, 'count' => $count, 'message' => "{$count} subscriber(s) updated."]);
    }

    // =========================================================================
    // CAMPAIGNS
    // =========================================================================

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $this->validateCampaign($request);
        $campaign = $this->newsletterService->createCampaign($data);

        return response()->json(['success' => true, 'message' => 'Campaign saved.', 'data' => $campaign], 201);
    }

    public function updateCampaign(Request $request, NewsletterCampaign $campaign): JsonResponse
    {
        if ($campaign->status === NewsletterCampaign::STATUS_SENT) {
            return response()->json(['success' => false, 'message' => 'A sent campaign cannot be edited.'], 422);
        }
        $data = $this->validateCampaign($request);
        $campaign = $this->newsletterService->updateCampaign($campaign, $data);

        return response()->json(['success' => true, 'message' => 'Campaign updated.', 'data' => $campaign]);
    }

    public function destroyCampaign(NewsletterCampaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json(['success' => true, 'message' => 'Campaign deleted.']);
    }

    public function sendCampaign(Request $request, NewsletterCampaign $campaign): JsonResponse
    {
        if ($campaign->status === NewsletterCampaign::STATUS_SENT) {
            return response()->json(['success' => false, 'message' => 'Campaign already sent.'], 422);
        }

        if ($request->filled('scheduled_at')) {
            $campaign = $this->newsletterService->scheduleCampaign($campaign, $request->input('scheduled_at'));

            return response()->json(['success' => true, 'message' => 'Campaign scheduled.', 'data' => $campaign]);
        }

        $campaign = $this->newsletterService->sendCampaign($campaign);

        return response()->json([
            'success' => true,
            'message' => "Campaign sent to {$campaign->sent_count} subscribers.",
            'data' => $campaign,
        ]);
    }

    private function validateCampaign(Request $request): array
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'body' => 'nullable|string|max:100000',
            'audience_type' => 'required|string|in:all_confirmed,source',
            'audience_source' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:draft,scheduled',
        ]);
        $data['name'] = $data['name'] ?? $data['subject'];

        return $data;
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
