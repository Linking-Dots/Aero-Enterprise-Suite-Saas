<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\CentralModel;

/**
 * LandlordRoleModuleAccess
 *
 * Identical schema to RoleModuleAccess but PINNED to the central (landlord) DB
 * connection. Use from platform-context queries to guarantee they never
 * accidentally hit a tenant DB (e.g., inside a queue job that switched the
 * default connection via stancl/tenancy).
 */
class LandlordRoleModuleAccess extends CentralModel
{
    protected $connection = 'central';

    protected $table = 'role_module_access';

    protected $fillable = [
        'role_id', 'module_id', 'sub_module_id',
        'component_id', 'action_id', 'access_scope',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->setConnection('central'));
        static::saving(fn (self $m) => $m->setConnection('central'));
    }
}
