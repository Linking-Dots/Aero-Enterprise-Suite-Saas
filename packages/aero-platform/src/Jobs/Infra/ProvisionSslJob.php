<?php

declare(strict_types=1);

namespace Aero\Platform\Jobs\Infra;

use Aero\Platform\Models\Infra\TenantCustomDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches Let's Encrypt SSL provisioning for a custom domain.
 *
 * In v1 this is a stub — actual ACME challenge is handled via
 * Certbot/acme.sh on the infra layer. The job records the outcome.
 */
class ProvisionSslJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(private readonly int $domainId) {}

    public function handle(): void
    {
        $domain = TenantCustomDomain::find($this->domainId);

        if (! $domain) {
            Log::warning("ProvisionSslJob: domain {$this->domainId} not found");

            return;
        }

        // In production: invoke ACME client here.
        // For now, mark as active with a 90-day expiry (Let's Encrypt default).
        $domain->update([
            'ssl_status' => 'active',
            'ssl_expires_at' => now()->addDays(90),
        ]);
    }
}
