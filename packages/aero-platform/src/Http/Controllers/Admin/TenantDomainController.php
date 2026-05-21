<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Domain;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDomainController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'domains' => $tenant->domains()->orderByDesc('is_primary')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255|unique:domains,domain',
            'is_primary' => 'boolean',
        ]);

        DB::transaction(function () use ($tenant, $data) {
            if ($data['is_primary'] ?? false) {
                $tenant->domains()->update(['is_primary' => false]);
            }

            $domain = Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $data['domain'],
                'is_primary' => $data['is_primary'] ?? false,
                'status' => 'pending',
                'ssl_status' => 'pending',
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_DOMAIN_ADDED->value,
                action: 'store',
                subject: $domain,
                description: "Domain {$data['domain']} added to tenant {$tenant->name}"
            );
        });

        return back()->with('success', 'Domain added');
    }

    public function destroy(Tenant $tenant, Domain $domain): RedirectResponse
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);

        DB::transaction(function () use ($tenant, $domain) {
            $domainName = $domain->domain;

            $domain->delete();

            $this->audit->log(
                event: AuditEventType::TENANT_DOMAIN_REMOVED->value,
                action: 'destroy',
                subject: $tenant,
                description: "Domain {$domainName} removed from tenant {$tenant->name}"
            );
        });

        return back()->with('success', 'Domain removed');
    }

    public function verify(Tenant $tenant, Domain $domain): RedirectResponse
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);

        DB::transaction(function () use ($tenant, $domain) {
            $domain->markAsVerified();

            $this->audit->log(
                event: AuditEventType::TENANT_DOMAIN_VERIFIED->value,
                action: 'verify',
                subject: $domain,
                description: "Domain {$domain->domain} verified for tenant {$tenant->name}"
            );
        });

        return back()->with('success', 'Domain verified');
    }
}
