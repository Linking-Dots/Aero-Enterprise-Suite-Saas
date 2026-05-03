<?php

namespace Aero\Platform\Http\Controllers\Billing;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Billing\SslCommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Cashier\Exceptions\IncompletePayment;

/**
 * Landlord Billing Controller
 *
 * Handles Stripe subscription checkout, billing portal, and payment management
 * for tenants on the platform level (Landlord side).
 */
class BillingController extends Controller
{
    /**
     * Display the billing dashboard for a tenant.
     */
    public function index(Tenant $tenant): InertiaResponse
    {
        return Inertia::render('Platform/Admin/Billing/TenantBilling', [
            'tenant' => $tenant->load('plan', 'billingAddress'),
            'subscription' => $tenant->subscription('default'),
            'invoices' => $tenant->invoices()->take(10),
            'paymentMethods' => $tenant->paymentMethods(),
            'defaultPaymentMethod' => $tenant->defaultPaymentMethod(),
        ]);
    }

    /**
     * Initiate a Stripe Checkout session for a new subscription.
     *
     * This method creates a Stripe Checkout session that redirects the user
     * to Stripe's hosted payment page. After successful payment, they are
     * redirected back to the success URL.
     */
    public function checkout(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        // Determine the correct Stripe Price ID based on billing cycle
        $priceId = $validated['billing_cycle'] === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return response()->json([
                'success' => false,
                'message' => "No Stripe price configured for {$validated['billing_cycle']} billing.",
            ], 422);
        }

        try {
            // Build the checkout session
            $checkout = $tenant->newSubscription('default', $priceId)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => $validated['success_url'].'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $validated['cancel_url'],
                    'customer_email' => $tenant->stripeEmail(),
                    'client_reference_id' => $tenant->id,
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $validated['billing_cycle'],
                    ],
                    'subscription_data' => [
                        'metadata' => [
                            'tenant_id' => $tenant->id,
                            'plan_id' => $plan->id,
                        ],
                    ],
                ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle subscription checkout with existing payment method.
     *
     * For tenants that already have a payment method on file,
     * this creates the subscription directly without redirecting to Stripe.
     */
    public function subscribe(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'payment_method' => ['nullable', 'string'], // Stripe PaymentMethod ID
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        // Determine the correct Stripe Price ID
        $priceId = $validated['billing_cycle'] === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return response()->json([
                'success' => false,
                'message' => "No Stripe price configured for {$validated['billing_cycle']} billing.",
            ], 422);
        }

        try {
            $subscriptionBuilder = $tenant->newSubscription('default', $priceId);

            // Add trial days if plan has them and tenant hasn't used trial
            if ($plan->trial_days > 0 && ! $tenant->hasEverSubscribedTo('default')) {
                $subscriptionBuilder->trialDays($plan->trial_days);
            }

            // Use provided payment method or default
            if (! empty($validated['payment_method'])) {
                $subscriptionBuilder->create($validated['payment_method']);
            } else {
                $subscriptionBuilder->create();
            }

            // Enrich the Cashier-created subscription row with our lifecycle fields
            $subscription = $tenant->subscription('default');
            if ($subscription) {
                $amount = $validated['billing_cycle'] === 'yearly'
                    ? $plan->yearly_price
                    : $plan->monthly_price;

                $subscription->update([
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'amount' => $amount,
                    'currency' => $plan->currency ?? config('cashier.currency', 'usd'),
                    'status' => \Aero\Platform\Models\Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($plan->duration_in_months ?? 1),
                ]);
            }

            // Activate tenant
            $tenant->update([
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully.',
                'subscription' => $tenant->fresh()->subscription('default'),
            ]);
        } catch (IncompletePayment $e) {
            return response()->json([
                'success' => false,
                'requires_action' => true,
                'payment_intent_client_secret' => $e->payment->clientSecret(),
                'message' => 'Additional payment authentication required.',
            ], 402);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate an SSL Commerz checkout session for regional payment.
     *
     * Returns a GatewayPageURL that the frontend should redirect to.
     */
    public function sslCommerzCheckout(Request $request, Plan $plan, SslCommerzService $sslCommerz): JsonResponse
    {
        if (! $sslCommerz->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Regional payment gateway is not configured.',
            ], 422);
        }

        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email'],
            'customer_phone' => ['required', 'string', 'max:20'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $amount = $validated['billing_cycle'] === 'yearly'
            ? $plan->yearly_price
            : $plan->monthly_price;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid plan price.',
            ], 422);
        }

        $result = $sslCommerz->initiatePayment([
            'amount' => $amount,
            'currency' => config('sslcommerz.currency', 'BDT'),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $validated['billing_cycle'],
            'product_name' => "{$plan->name} — {$validated['billing_cycle']}",
        ]);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment initiation failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'gateway_url' => $result['gateway_url'],
            'session_key' => $result['session_key'],
        ]);
    }

    /**
     * Swap subscription to a different plan.
     */
    public function changePlan(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $priceId = $validated['billing_cycle'] === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return response()->json([
                'success' => false,
                'message' => "No Stripe price configured for {$validated['billing_cycle']} billing.",
            ], 422);
        }

        try {
            $subscription = $tenant->subscription('default');
            $subscription->swap($priceId);

            // Update subscription row with new plan details
            $amount = $validated['billing_cycle'] === 'yearly'
                ? $plan->yearly_price
                : $plan->monthly_price;

            $subscription->update([
                'plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'amount' => $amount,
                'currency' => $plan->currency ?? config('cashier.currency', 'usd'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan changed successfully.',
                'subscription' => $tenant->fresh()->subscription('default'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change plan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'immediately' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $subscription = $tenant->subscription('default');

            if (! $subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found.',
                ], 404);
            }

            if ($validated['immediately'] ?? false) {
                $subscription->cancelNow();
            } else {
                $subscription->cancel();
            }

            // Store cancellation reason
            if (! empty($validated['reason'])) {
                $subscription->update([
                    'cancellation_reason' => $validated['reason'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Tenant $tenant): JsonResponse
    {
        try {
            $subscription = $tenant->subscription('default');

            if (! $subscription || ! $subscription->onGracePeriod()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscription available to resume.',
                ], 404);
            }

            $subscription->resume();

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Stripe Customer Portal URL.
     *
     * The billing portal allows customers to manage their subscription,
     * update payment methods, and view invoices directly on Stripe.
     */
    public function portal(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'return_url' => ['required', 'url'],
        ]);

        try {
            $url = $tenant->billingPortalUrl($validated['return_url']);

            return response()->json([
                'success' => true,
                'portal_url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create billing portal session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate a Stripe Checkout session for a module add-on subscription.
     *
     * Allows tenants to purchase individual modules that are not included
     * in their current plan. Creates a recurring subscription for the module.
     */
    public function moduleCheckout(Request $request, Module $module): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $pricing = \DB::table('module_pricing')
            ->where('module_code', $module->code)
            ->where('is_active', true)
            ->first();

        if (! $pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Module has no pricing configured.',
            ], 422);
        }

        $price = $validated['billing_cycle'] === 'yearly'
            ? $pricing->yearly_price
            : $pricing->monthly_price;

        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Module has no price configured for '.$validated['billing_cycle'].' billing.',
            ], 422);
        }

        try {
            $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));

            // Ensure tenant has a Stripe customer
            if (! $tenant->stripe_id) {
                $customer = $stripeClient->customers->create([
                    'email' => $tenant->stripeEmail(),
                    'metadata' => ['tenant_id' => $tenant->id],
                ]);
                $tenant->update(['stripe_id' => $customer->id]);
            }

            $session = $stripeClient->checkout->sessions->create([
                'customer' => $tenant->stripe_id,
                'mode' => 'subscription',
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('cashier.currency', 'usd'),
                        'product_data' => ['name' => $module->name],
                        'unit_amount' => (int) ($price * 100),
                        'recurring' => [
                            'interval' => $validated['billing_cycle'] === 'yearly' ? 'year' : 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $validated['success_url'].'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $validated['cancel_url'],
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'module_id' => $module->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'type' => 'module_addon',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                        'type' => 'module_addon',
                    ],
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create module checkout: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the tenant's invoices.
     */
    public function invoices(Tenant $tenant): JsonResponse
    {
        try {
            $invoices = $tenant->invoices()->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toFormattedDateString(),
                    'total' => $invoice->total(),
                    'status' => $invoice->status,
                    'pdf_url' => $invoice->invoicePdf(),
                ];
            });

            return response()->json([
                'success' => true,
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a specific invoice as PDF.
     */
    public function downloadInvoice(Tenant $tenant, string $invoiceId)
    {
        return $tenant->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    }

    /**
     * Update tenant's billing address.
     */
    public function updateBillingAddress(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'tax_id_type' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $tenant->billingAddress()->updateOrCreate(
                ['tenant_id' => $tenant->id],
                $validated
            );

            // Sync address to Stripe customer
            if ($tenant->hasStripeId()) {
                $tenant->updateStripeCustomer([
                    'address' => $tenant->stripeAddress(),
                    'tax_id_data' => $validated['tax_id'] ? [[
                        'type' => $validated['tax_id_type'] ?? 'eu_vat',
                        'value' => $validated['tax_id'],
                    ]] : null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Billing address updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update billing address: '.$e->getMessage(),
            ], 500);
        }
    }
}
