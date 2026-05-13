<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Billing Address Model
 *
 * Stores billing/invoicing address for Stripe customers.
 * Required for proper tax calculations and invoice generation.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string|null $company_name
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string $country ISO 3166-1 alpha-2
 * @property string|null $tax_id VAT/GST number
 * @property string|null $tax_id_type eu_vat, us_ein, etc.
 */
class TenantBillingAddress extends CentralModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'company_name',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'tax_id_type',
    ];

    /**
     * Get the tenant that owns this billing address.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Format the full address as a string.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
