<?php

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\BulkOperation;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteBulkTenantAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bulkOpId,
        public string $tenantId,
        public string $type,
        public array $payload,
    ) {}

    public function handle(TenantAdminService $svc): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        match ($this->type) {
            'suspend' => $tenant->status === 'suspended' ? null : $svc->suspend($tenant, $this->payload['reason'] ?? 'Bulk operation'),
            'reactivate' => $svc->activate($tenant),
            'archive' => $svc->archive($tenant),
            'plan-change' => null, // handled by billing service in P-2
            'email' => null, // notification job dispatched elsewhere
            default => null,
        };

        $op = BulkOperation::find($this->bulkOpId);

        if ($op) {
            $op->increment('processed');

            if ($op->processed >= $op->total) {
                $op->update(['status' => 'completed']);
            }
        }
    }
}
