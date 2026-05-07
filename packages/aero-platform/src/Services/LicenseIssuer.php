<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Support\Str;

class LicenseIssuer
{
    /**
     * Generate a unique license key for the given product code.
     * Format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX
     *
     * The first two characters of the last segment are a checksum of the
     * first three segments + salt (matches LicenseValidator in aero-core).
     */
    public function generateKey(string $productCode): string
    {
        do {
            $seg1 = strtoupper(Str::random(8));
            $seg2 = strtoupper(Str::random(8));
            $seg3 = strtoupper(Str::random(8));

            $salt = config('license.checksum_salt', 'aero-license-salt');
            $checksum = strtoupper(substr(md5($seg1.$seg2.$seg3.$salt), 0, 2));
            $seg4 = $checksum.strtoupper(Str::random(6));

            $key = "{$seg1}-{$seg2}-{$seg3}-{$seg4}";

        } while (StandaloneLicense::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Issue a new license to a customer after purchase.
     */
    public function issue(
        string $productCode,
        string $customerEmail,
        string $billingType,
        string $source,
        ?string $orderId = null,
        ?string $customerName = null,
        int $maxActivations = 1,
    ): StandaloneLicense {
        $product = Product::active()->where('code', $productCode)->firstOrFail();
        $key = $this->generateKey($productCode);
        $expiresAt = $billingType === 'annual' ? now()->addYear() : null;

        return StandaloneLicense::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'license_key' => $key,
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
            'status' => 'active',
            'billing_type' => $billingType,
            'purchase_source' => $source,
            'external_order_id' => $orderId,
            'max_activations' => $maxActivations,
            'activation_count' => 0,
            'expires_at' => $expiresAt,
        ]);
    }
}
