<?php

declare(strict_types=1);

namespace Aero\Assistant\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Context-neutral base for Aeon's own tables (conversations, messages,
 * embeddings). Aeon runs in BOTH the tenant workspace AND the platform/central
 * admin, so its models must follow the DEFAULT connection the request context
 * provides — the tenant DB when stancl/tenancy has switched it, the central DB
 * on the landlord domain, or the single DB in standalone.
 *
 * Deliberately NOT a TenantModel: that base fails-closed outside tenant context
 * (correct for tenant-only business tables), which would make Aeon unusable on
 * the platform side. Isolation here comes from the active connection, not a
 * model guard — the aeon_* tables are provisioned into every DB (sharable tier).
 */
abstract class AeonModel extends Model
{
    //
}
