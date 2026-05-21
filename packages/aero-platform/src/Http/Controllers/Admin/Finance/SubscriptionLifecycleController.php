<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Contracts\BillableSubscription;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\ConvertTrialRequest;
use Aero\Platform\Http\Requests\Finance\ExecutePlanChangeRequest;
use Aero\Platform\Http\Requests\Finance\ExtendTrialRequest;
use Aero\Platform\Http\Requests\Finance\PauseSubscriptionRequest;
use Aero\Platform\Http\Requests\Finance\UpdateCancellationFlowRequest;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\Finance\SubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionLifecycleController extends Controller
{
    public function __construct(private readonly SubscriptionLifecycleService $service) {}

    public function trials(Request $request): Response
    {
        $trials = $this->service->listTrials($request->only(['billable_type']));

        return Inertia::render('Platform/Admin/SubscriptionLifecycle/Index', [
            'tab' => 'trials',
            'trials' => $trials,
        ]);
    }

    public function extend(ExtendTrialRequest $request, string $id): JsonResponse
    {
        $subscription = $this->resolveSubscription($request->validated('billable_type'), $id);
        $subscription = $this->service->extendTrial(
            $subscription,
            (int) $request->validated('days'),
            (int) $request->user()->id
        );

        return response()->json(['subscription' => $subscription]);
    }

    public function convert(ConvertTrialRequest $request, string $id): JsonResponse
    {
        $subscription = $this->resolveSubscription($request->validated('billable_type'), $id);
        $subscription = $this->service->convertTrial(
            $subscription,
            (int) $request->validated('target_id'),
            (int) $request->user()->id
        );

        return response()->json(['subscription' => $subscription]);
    }

    public function previewProration(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'billable_type' => ['required', 'in:plan,product'],
            'new_target_id' => ['required', 'integer', 'min:1'],
        ]);

        $subscription = $this->resolveSubscription($request->input('billable_type'), $id);
        $preview = $this->service->previewProration(
            $subscription,
            (int) $request->input('new_target_id')
        );

        return response()->json(['preview' => $preview]);
    }

    public function executeChange(ExecutePlanChangeRequest $request, string $id): JsonResponse
    {
        $subscription = $this->resolveSubscription($request->validated('billable_type'), $id);
        $subscription = $this->service->executeChange(
            $subscription,
            (int) $request->validated('new_target_id'),
            (int) $request->user()->id
        );

        return response()->json(['subscription' => $subscription]);
    }

    public function pause(PauseSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->resolveSubscription($request->validated('billable_type'), $id);
        $resumeAt = $request->validated('resume_at')
            ? Carbon::parse($request->validated('resume_at'))
            : null;

        $subscription = $this->service->pause($subscription, $resumeAt, (int) $request->user()->id);

        return response()->json(['subscription' => $subscription]);
    }

    public function resume(Request $request, string $id): JsonResponse
    {
        $request->validate(['billable_type' => ['required', 'in:plan,product']]);
        $subscription = $this->resolveSubscription($request->input('billable_type'), $id);
        $subscription = $this->service->resume($subscription, (int) $request->user()->id);

        return response()->json(['subscription' => $subscription]);
    }

    public function cancellations(Request $request): Response
    {
        $cancellations = $this->service->listCancellations($request->only(['billable_type']));

        return Inertia::render('Platform/Admin/SubscriptionLifecycle/Index', [
            'tab' => 'cancellations',
            'cancellations' => $cancellations,
        ]);
    }

    public function updateCancellationFlow(UpdateCancellationFlowRequest $request): JsonResponse
    {
        $config = $this->service->updateCancellationFlow(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['config' => $config]);
    }

    private function resolveSubscription(string $billableType, string $id): BillableSubscription
    {
        if ($billableType === 'plan') {
            return Subscription::findOrFail($id);
        }

        return ProductSubscription::findOrFail($id);
    }
}
