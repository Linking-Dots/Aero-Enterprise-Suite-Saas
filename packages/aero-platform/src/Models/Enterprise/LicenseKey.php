<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenseKey extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'key', 'product_codes', 'plan_code', 'max_activations', 'activation_count',
        'issued_to_name', 'issued_to_email', 'expires_at', 'revoked_at', 'created_by',
    ];

    protected $hidden = ['key'];

    protected $casts = [
        'product_codes' => 'array',
        'max_activations' => 'integer',
        'activation_count' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class, 'license_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function canActivate(): bool
    {
        return ! $this->isRevoked()
            && ! $this->isExpired()
            && $this->activation_count < $this->max_activations;
    }

    public function entitles(string $productCode): bool
    {
        return in_array($productCode, (array) $this->product_codes, true);
    }
}
