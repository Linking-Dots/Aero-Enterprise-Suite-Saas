<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StandaloneLicense extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'product_id', 'license_key', 'customer_email', 'customer_name',
        'status', 'bound_domain_hash', 'activation_count', 'max_activations',
        'purchase_source', 'external_order_id', 'billing_type',
        'expires_at', 'last_validated_at', 'current_version', 'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_validated_at' => 'datetime',
        'activation_count' => 'integer',
        'max_activations' => 'integer',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isDomainBound(): bool
    {
        return $this->bound_domain_hash !== null;
    }

    public function domainMatches(string $domainHash): bool
    {
        if (! $this->isDomainBound()) {
            return true;
        }

        return hash_equals($this->bound_domain_hash, $domainHash);
    }

    public function canActivateOnNewDomain(): bool
    {
        return $this->activation_count < $this->max_activations;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
