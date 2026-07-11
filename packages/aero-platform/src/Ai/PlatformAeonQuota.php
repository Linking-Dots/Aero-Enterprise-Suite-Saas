<?php

declare(strict_types=1);

namespace Aero\Platform\Ai;

use Aero\Contracts\Ai\AeonQuotaContract;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Quotas\QuotaEnforcementService;

/**
 * Binds the assistant's AI quota to the platform quota system for the CURRENT
 * tenant. The assistant always runs inside an initialized tenant context, so
 * the active tenant is resolved via stancl's tenant() helper; the plan's AI
 * allowance is then read/metered through QuotaEnforcementService (the same path
 * as every other resource). No tenant in context → unmetered (fail-open).
 */
class PlatformAeonQuota implements AeonQuotaContract
{
    public function __construct(private QuotaEnforcementService $quotas) {}

    /** @return array<string,mixed> */
    public function status(): array
    {
        $tenant = $this->currentTenant();
        if (! $tenant) {
            return ['enabled' => true, 'allowed' => true, 'used' => 0, 'limit' => -1, 'remaining' => -1, 'model' => 'all'];
        }

        $summary = $this->quotas->getAiSummary($tenant);

        return [
            'enabled' => $summary['enabled'],
            'allowed' => $this->quotas->canSendAiMessage($tenant),
            'used' => $summary['used'],
            'limit' => $summary['limit'],
            'remaining' => $summary['remaining'],
            'model' => $summary['model'],
        ];
    }

    public function record(): void
    {
        $tenant = $this->currentTenant();
        if ($tenant) {
            $this->quotas->incrementAiMessages($tenant);
        }
    }

    private function currentTenant(): ?Tenant
    {
        if (! function_exists('tenant')) {
            return null;
        }
        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : ($tenant?->id ? Tenant::find($tenant->id) : null);
    }
}
