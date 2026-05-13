<?php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Aero\Platform\Services\LicenseIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class PurchaseController extends Controller
{
    public function __construct(private readonly LicenseIssuer $licenseIssuer) {}

    public function checkout(Request $request, string $productCode): Response
    {
        $product = Product::active()->where('code', $productCode)->firstOrFail();
        $billingCycle = $request->query('cycle', 'one_time');

        $price = match ($billingCycle) {
            'annual' => $product->yearly_price,
            'monthly' => $product->monthly_price,
            default => $product->monthly_price,
        };

        return Inertia::render('Marketplace/Checkout', [
            'product' => $product,
            'billingCycle' => $billingCycle,
            'price' => $price,
            'currency' => $product->currency,
        ]);
    }

    public function createCheckoutSession(Request $request): RedirectResponse
    {
        $request->validate([
            'product_code' => ['required', 'string'],
            'billing_cycle' => ['required', 'in:one_time,annual'],
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::active()->where('code', $request->product_code)->firstOrFail();
        $amount = $request->billing_cycle === 'annual'
            ? $product->yearly_price
            : $product->monthly_price;

        try {
            $stripe = new StripeClient(config('cashier.secret'));

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($product->currency),
                        'product_data' => ['name' => $product->name],
                        'unit_amount' => (int) ($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' => $request->email,
                'metadata' => [
                    'product_code' => $product->code,
                    'billing_cycle' => $request->billing_cycle,
                    'customer_name' => $request->name ?? '',
                ],
                'success_url' => route('marketplace.purchase.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('marketplace.product', $product->code),
            ]);

            return redirect($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe checkout session creation failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['payment' => 'Payment setup failed. Please try again.']);
        }
    }

    public function webhook(Request $request): HttpResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('cashier.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if ($session->payment_status === 'paid') {
                $this->handleSuccessfulPurchase($session);
            }
        }

        return response('ok', 200);
    }

    public function success(): Response
    {
        return Inertia::render('Marketplace/PurchaseSuccess', [
            'message' => 'Purchase successful. Check your email for your license key and download link.',
        ]);
    }

    private function handleSuccessfulPurchase(object $session): void
    {
        try {
            $orderId = $session->payment_intent;

            // Idempotency check: skip if this order was already processed
            if (StandaloneLicense::where('external_order_id', $orderId)->exists()) {
                Log::info('Duplicate marketplace webhook ignored', ['payment_intent' => $orderId]);

                return;
            }

            $metadata = (array) $session->metadata;
            $productCode = $metadata['product_code'];
            $billingType = $metadata['billing_cycle'] === 'annual' ? 'annual' : 'one_time';

            $license = $this->licenseIssuer->issue(
                productCode: $productCode,
                customerEmail: $session->customer_email,
                billingType: $billingType,
                source: 'marketplace',
                orderId: $orderId,
                customerName: $metadata['customer_name'] ?? null,
            );

            Log::info('License issued after purchase', [
                'license_key' => $license->license_key,
                'customer_email' => $session->customer_email,
                'product_code' => $productCode,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to issue license after purchase', [
                'error' => $e->getMessage(),
                'session' => $session->id,
            ]);
        }
    }
}
