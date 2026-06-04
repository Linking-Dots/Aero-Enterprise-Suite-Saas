<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

/**
 * Lets a consuming package (e.g. the SaaS platform) filter which discovered module
 * definitions are synced for a given scope — WITHOUT HRMAC knowing anything about
 * tenants, subscriptions, or any other context.
 *
 * HRMAC's sync command resolves this from the container only if a consumer has bound
 * it; otherwise every discovered module is synced (the pure default). This keeps the
 * "which modules does this context get" data-decision in the sharing package.
 *
 * @see \Aero\HRMAC\Console\Commands\SyncModuleHierarchy
 */
interface ModuleSyncFilterInterface
{
    /**
     * @param  Collection<int, array<string, mixed>>  $modules  Discovered module definitions.
     * @param  string  $scope  The sync scope ('tenant', 'platform', or 'all').
     * @return Collection<int, array<string, mixed>>  The filtered set to sync.
     */
    public function filter(Collection $modules, string $scope): Collection;
}
