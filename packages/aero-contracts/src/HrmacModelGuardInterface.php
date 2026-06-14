<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Optional, consumer-supplied query guard for HRMAC's (context-free) models.
 *
 * HRMAC models live in whatever database the host's runtime context points the
 * default connection at (tenant DB in a tenant request, central DB in a platform
 * request, the single DB in standalone). HRMAC itself enforces nothing.
 *
 * A consuming package MAY bind this contract to re-apply isolation rules — e.g. the
 * tenant host asserting that tenant-scoped HRMAC tables are only queried inside an
 * initialized tenant context (or a legitimate platform/central context). If nothing
 * is bound, HRMAC models query freely on the default connection.
 *
 * @see \Aero\HRMAC\Models\HrmacModel
 */
interface HrmacModelGuardInterface
{
    /**
     * Called for every HRMAC model query. Implementations MUST throw
     * (\LogicException) to deny a query made in an unsafe context.
     *
     * @param  string  $modelClass  The HRMAC model being queried.
     */
    public function assert(string $modelClass): void;
}
