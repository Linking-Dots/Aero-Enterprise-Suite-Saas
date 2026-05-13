<?php

namespace Aero\Platform\Http\Controllers\Api;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;

class LicenseController extends Controller
{
    /**
     * POST /api/license/activate
     *
     * Called by standalone installer. Binds domain on first activation.
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'domain' => ['required', 'string'],
        ]);

        $license = StandaloneLicense::with('product')
            ->where('license_key', $request->license_key)
            ->first();

        if (! $license || ! $license->isActive()) {
            return response()->json(['status' => 'invalid', 'message' => 'License not found or inactive.']);
        }

        if ($license->product->code !== $request->product_id) {
            return response()->json(['status' => 'invalid', 'message' => 'License does not belong to this product.']);
        }

        $domainHash = hash('sha256', strtolower($request->domain));

        if ($license->isDomainBound() && ! $license->domainMatches($domainHash)) {
            if (! $license->canActivateOnNewDomain()) {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'License is already activated on another domain. Contact support to transfer.',
                ]);
            }
        }

        if (! $license->isDomainBound()) {
            $license->update([
                'bound_domain_hash' => $domainHash,
                'activation_count' => 1,
                'last_validated_at' => now(),
            ]);
        } else {
            $license->update(['last_validated_at' => now()]);
        }

        return response()->json([
            'status' => 'valid',
            'product_id' => $license->product->code,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * POST /api/license/validate
     *
     * Called daily by standalone installations.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'domain_hash' => ['required', 'string'],
        ]);

        $license = StandaloneLicense::with('product')
            ->where('license_key', $request->license_key)
            ->first();

        if (! $license) {
            return response()->json(['status' => 'invalid']);
        }

        if ($license->product->code !== $request->product_id) {
            return response()->json(['status' => 'invalid']);
        }

        if (! $license->domainMatches($request->domain_hash)) {
            return response()->json(['status' => 'invalid', 'message' => 'Domain mismatch.']);
        }

        if ($license->expires_at !== null && $license->expires_at->isPast()) {
            return response()->json([
                'status' => 'expired',
                'expired_at' => $license->expires_at->toIso8601String(),
                'message' => 'License expired. Renew at aerosuite.com/renew',
            ]);
        }

        if ($license->status !== 'active') {
            return response()->json(['status' => 'invalid', 'message' => "License status: {$license->status}"]);
        }

        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'status' => 'valid',
            'product_id' => $license->product->code,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/marketplace/catalog
     *
     * Public product catalog for standalone admin panel add-on pages.
     */
    public function catalog(): JsonResponse
    {
        $products = Product::marketplaceVisible()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'module_code', 'name', 'description', 'icon',
                'monthly_price', 'yearly_price', 'currency', 'version', 'metadata']);

        return response()->json([
            'products' => $products,
            'marketplace_url' => config('app.url').'/marketplace',
            'cached_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * POST /api/license/download-url
     *
     * Returns a signed download URL for the licensed product ZIP.
     */
    public function downloadUrl(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_id' => ['required', 'string'],
        ]);

        $license = StandaloneLicense::with('product')
            ->where('license_key', $request->license_key)
            ->first();

        if (! $license || ! $license->isActive()) {
            return response()->json(['error' => 'Invalid or inactive license.'], 403);
        }

        if ($license->product->code !== $request->product_id) {
            return response()->json(['error' => 'License does not match product.'], 403);
        }

        $signedUrl = URL::temporarySignedRoute(
            'marketplace.download',
            now()->addHours(48),
            ['license' => $license->id, 'product' => $license->product->code]
        );

        // Return sha256 from product metadata if stored (set when ZIP is uploaded to marketplace)
        $zipSha256 = data_get($license->product->metadata, 'zip_sha256');

        return response()->json([
            'download_url' => $signedUrl,
            'expected_sha256' => $zipSha256,
        ]);
    }
}
