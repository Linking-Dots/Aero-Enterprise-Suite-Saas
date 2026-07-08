<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionAdminService $svc
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Subscriptions', [
            ...$this->svc->overview(),
            'plans' => Plan::orderBy('name')->get(['id', 'name', 'price_monthly', 'price_annual']),
            'tenants' => DB::table('tenants')->orderBy('name')->limit(500)->get(['id', 'name']),
            'products' => DB::table('products')->orderBy('name')->get(['id', 'name', 'monthly_price', 'yearly_price']),
        ]);
    }

    /** Guided create — plan or product subscription for a tenant. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'kind' => ['required', 'in:plan,product'],
            'plan_id' => ['required_if:kind,plan', 'nullable', 'string', 'exists:plans,id'],
            'product_id' => ['required_if:kind,product', 'nullable', 'string', 'exists:products,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $this->svc->store($data);

        return back()->with('success', 'Subscription created.');
    }

    public function show(Subscription $subscription): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/SubscriptionShow', [
            'subscription' => $subscription->load(['plan', 'owner']),
        ]);
    }

    /** Drawer payload: recent invoices, audit activity, payment method, discount. */
    public function detail(string $kind, string $id): JsonResponse
    {
        abort_unless(in_array($kind, ['plan', 'product'], true), 404);

        return response()->json($this->svc->detail($kind, $id));
    }

    /** Stream every subscription (both kinds) as CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->svc->exportRows();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['kind', 'tenant', 'label', 'status', 'billing_cycle', 'mrr', 'charge', 'currency', 'renews_at', 'trial_ends_at', 'started_at', 'cancelled_at']);
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
            fclose($out);
        }, 'subscriptions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'mode' => ['nullable', 'in:immediate,period_end'],
        ]);

        // Defense-in-depth: the UI never offers Cancel on a cancelled row
        // (it shows Reactivate), so a cancel here is an out-of-band request.
        abort_if($subscription->status === Subscription::STATUS_CANCELLED, 422, 'This subscription is already cancelled.');

        $mode = $request->string('mode')->toString() ?: 'immediate';
        $this->svc->cancel($subscription, $request->string('reason')->toString(), $mode);

        return back()->with('success', $mode === 'period_end'
            ? 'Subscription will cancel at period end.'
            : 'Subscription cancelled.');
    }

    public function changePlan(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,id'],
        ]);

        $this->svc->changePlan($subscription, $request->string('plan_id')->toString());

        return back()->with('success', 'Subscription plan updated.');
    }

    public function upgrade(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);
        $new = $this->svc->upgrade($subscription, $request->integer('plan_id'), $request->user()->id);

        return redirect()->route('platform.admin.billing.subscriptions.show', $new)
            ->with('success', 'Subscription upgraded');
    }

    public function reactivate(Subscription $subscription): RedirectResponse
    {
        $this->svc->reactivate($subscription);

        return back()->with('success', 'Subscription reactivated.');
    }

    public function pause(Subscription $subscription): RedirectResponse
    {
        try {
            $this->svc->pause($subscription);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Subscription paused.');
    }

    public function resume(Subscription $subscription): RedirectResponse
    {
        try {
            $this->svc->resume($subscription);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Subscription resumed.');
    }

    public function extendTrial(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate(['days' => ['required', 'integer', 'min:1', 'max:60']]);

        try {
            $this->svc->extendTrial($subscription, $request->integer('days'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Trial extended.');
    }

    public function convertTrial(Subscription $subscription): RedirectResponse
    {
        try {
            $this->svc->convertTrial($subscription);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Trial converted to an active subscription.');
    }

    public function changeCycle(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate(['billing_cycle' => ['required', 'in:monthly,yearly']]);

        $this->svc->changeCycle($subscription, $request->string('billing_cycle')->toString());

        return back()->with('success', 'Billing cycle updated.');
    }

    public function retryCharge(Subscription $subscription): RedirectResponse
    {
        $this->svc->retryCharge($subscription);

        return back()->with('success', 'Payment retry queued for the dunning scheduler.');
    }

    public function remind(Subscription $subscription): RedirectResponse
    {
        return $this->svc->remind($subscription)
            ? back()->with('success', 'Payment reminder queued.')
            : back()->with('error', 'No tenant email on record — reminder logged only.');
    }

    /** Bulk cancel / remind over a mixed plan+product selection. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:cancel,remind'],
            'reason' => ['nullable', 'string', 'max:500'],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*.kind' => ['required', 'in:plan,product'],
            'ids.*.id' => ['required', 'string'],
        ]);

        $result = $this->svc->bulk($data['ids'], $data['action'], $data['reason'] ?? '');

        $msg = "{$result['ok']} processed".($result['failed'] > 0 ? ", {$result['failed']} failed" : '');

        return back()->with($result['failed'] > 0 ? 'error' : 'success', $msg);
    }

    /**
     * Cancel a PRODUCT subscription. The ProductSubscriptionObserver fires on the
     * status change → ProductSubscriptionChanged('cancelled') → catalog resync +
     * entitlement-ledger revoke + cache bust, so no extra bookkeeping is needed here.
     */
    public function cancelProduct(Request $request, ProductSubscription $productSubscription): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $productSubscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->string('reason')->toString() ?: null,
        ]);

        return back()->with('success', 'Product subscription cancelled.');
    }
}
