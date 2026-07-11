<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * The tenant-facing assistant's view of its AI quota for the CURRENT tenant.
 * The platform package binds an implementation that resolves the active tenant
 * and reads its plan's AI allowance (max_ai_messages / ai_model) via the quota
 * system. Unbound (standalone edition, isolated tests) → the assistant runs
 * unmetered, exactly as before.
 *
 * status() returns:
 *   [
 *     'enabled'   => bool,   is AI included in this tenant's plan?
 *     'allowed'   => bool,   may another message be sent this period?
 *     'used'      => int,
 *     'limit'     => int,    -1 = unlimited
 *     'remaining' => int,    -1 = unlimited
 *     'model'     => string, 'flash' | 'pro' | 'all'
 *   ]
 */
interface AeonQuotaContract
{
    /** @return array<string,mixed> */
    public function status(): array;

    /** Count one delivered assistant message against the monthly allowance. */
    public function record(): void;
}
