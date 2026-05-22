<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Jobs\Infra\ProvisionSslJob;
use Aero\Platform\Models\Infra\TenantCustomDomain;
use Illuminate\Support\Facades\DB;

class CustomDomainService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function addDomain(string $tenantId, string $domain): TenantCustomDomain
    {
        return DB::transaction(function () use ($tenantId, $domain): TenantCustomDomain {
            $txtRecord = 'aeos-verify='.hash('sha256', $tenantId.$domain.config('app.key'));

            $record = TenantCustomDomain::create([
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'status' => 'pending',
                'ssl_status' => 'none',
                'dns_txt_record' => $txtRecord,
            ]);

            $this->audit->log(
                event: AuditEventType::CUSTOM_DOMAIN_ADDED->value,
                action: 'add',
                subject: $record,
                description: "Custom domain {$domain} added for tenant {$tenantId}"
            );

            return $record;
        });
    }

    public function verifyDns(TenantCustomDomain $domain): bool
    {
        $records = dns_get_record($domain->domain, DNS_TXT);
        $expected = $domain->dns_txt_record;

        $matched = false;

        foreach ($records as $record) {
            if (isset($record['txt']) && $record['txt'] === $expected) {
                $matched = true;
                break;
            }
        }

        DB::transaction(function () use ($domain, $matched): void {
            if ($matched) {
                $domain->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);

                $this->audit->log(
                    event: AuditEventType::CUSTOM_DOMAIN_VERIFIED->value,
                    action: 'verify',
                    subject: $domain,
                    description: "Domain {$domain->domain} DNS verified"
                );
            } else {
                $domain->update(['status' => 'failed']);
            }
        });

        return $matched;
    }

    public function provisionSsl(TenantCustomDomain $domain): void
    {
        DB::transaction(function () use ($domain): void {
            $domain->update(['ssl_status' => 'provisioning']);

            ProvisionSslJob::dispatch($domain->id);

            $this->audit->log(
                event: AuditEventType::SSL_PROVISIONED->value,
                action: 'provision',
                subject: $domain,
                description: "SSL provisioning dispatched for {$domain->domain}"
            );
        });
    }

    public function renewSsl(TenantCustomDomain $domain): void
    {
        DB::transaction(function () use ($domain): void {
            $domain->update(['ssl_status' => 'provisioning']);

            ProvisionSslJob::dispatch($domain->id);

            $this->audit->log(
                event: AuditEventType::SSL_RENEWED->value,
                action: 'renew',
                subject: $domain,
                description: "SSL renewal dispatched for {$domain->domain}"
            );
        });
    }

    public function removeDomain(TenantCustomDomain $domain): void
    {
        DB::transaction(function () use ($domain): void {
            $domainName = $domain->domain;
            $tenantId = $domain->tenant_id;

            $domain->delete();

            $this->audit->log(
                event: AuditEventType::CUSTOM_DOMAIN_REMOVED->value,
                action: 'remove',
                subject: null,
                description: "Custom domain {$domainName} removed for tenant {$tenantId}"
            );
        });
    }
}
