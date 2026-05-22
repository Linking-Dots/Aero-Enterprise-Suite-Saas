<?php

declare(strict_types=1);

namespace Aero\Contracts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the CENTRAL (landlord) database.
 *
 * Central models are tenant-agnostic: Plans, LandlordUsers, Tenants, Products, etc.
 * The connection is pinned to 'central' and cannot be changed at runtime.
 *
 * NEVER add relationships to TenantModel subclasses — cross-DB joins fail.
 * Do NOT extend this in standalone mode (no 'central' connection exists).
 */
abstract class CentralModel extends Model
{
    protected $connection = 'central';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(fn (self $m) => $m->setConnection('central'));
        static::saving(fn (self $m) => $m->setConnection('central'));
    }

    /**
     * Default audit label — returns the model key as a string.
     * Subclasses may override to return a human-readable label (e.g. name, email).
     * Required by AuditService::log() which calls $subject->getAuditLabel().
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }
}
