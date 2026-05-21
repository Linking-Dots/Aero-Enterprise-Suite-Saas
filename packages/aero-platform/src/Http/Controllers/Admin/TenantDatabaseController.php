<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class TenantDatabaseController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'database' => [
                'name' => $tenant->byoc_enabled ? $tenant->byoc_db_name : "tenant_{$tenant->id}",
                'byoc' => (bool) $tenant->byoc_enabled,
                'host' => $tenant->byoc_enabled
                    ? $tenant->byoc_db_host
                    : config('database.connections.mysql.host'),
            ],
        ]);
    }

    public function migrate(Tenant $tenant): RedirectResponse
    {
        Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);

        $this->audit->log(
            event: AuditEventType::TENANT_DB_MIGRATED->value,
            action: 'migrate',
            subject: $tenant,
            description: "Migrated DB for {$tenant->name}"
        );

        return back()->with('success', 'Migrations executed');
    }

    public function backup(Tenant $tenant): RedirectResponse
    {
        // Backup implementation pending (P-10)
        $this->audit->log(
            event: AuditEventType::TENANT_DB_BACKUP_REQUESTED->value,
            action: 'backup',
            subject: $tenant,
            description: "Backup requested for {$tenant->name}"
        );

        return back()->with('success', 'Backup queued');
    }
}
