<?php

declare(strict_types=1);

namespace Aero\Platform\Listeners;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class ResyncTenantModuleCatalog
{
    public function handle(ProductSubscriptionChanged $event): void
    {
        $tenant = Tenant::find($event->subscription->tenant_id);
        if (! $tenant) {
            return;
        }

        tenancy()->initialize($tenant);
        try {
            Artisan::call('hrmac:sync-modules', ['--scope' => 'tenant']);
        } finally {
            tenancy()->end();
        }
    }
}
