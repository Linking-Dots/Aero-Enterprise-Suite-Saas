<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;

/**
 * Admin service for managing payment methods on behalf of tenants.
 * Delegates to the platform's payment gateway adapter (Stripe Cashier / manual).
 * Three-DS / SCA configuration is stored as a platform-level setting.
 */
class PaymentMethodAdminService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * List payment methods for a given tenant.
     * Returns a plain array — the underlying source is the payment gateway.
     */
    public function listForTenant(string $tenantId): array
    {
        // Stub: real implementation queries Stripe via Cashier or the stored vault.
        return [];
    }

    public function add(string $tenantId, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($tenantId, $data): array {
            // Stub: tokenize & attach to gateway customer record.
            $method = array_merge($data, [
                'id' => 'pm_stub_'.uniqid(),
                'tenant_id' => $tenantId,
                'is_default' => false,
            ]);

            $this->audit->log(
                event: AuditEventType::PAYMENT_METHOD_ADDED->value,
                action: 'added',
                subject: null,
                description: "Payment method added for tenant {$tenantId}.",
                metadata: ['tenant_id' => $tenantId, 'type' => $data['type'] ?? 'card'],
            );

            return $method;
        });
    }

    /**
     * Set the default payment method for a tenant.
     * Atomically clears the previous default and sets the new one.
     */
    public function setDefault(string $tenantId, string $methodId, int $actorId): void
    {
        DB::transaction(function () use ($tenantId, $methodId): void {
            // Stub: in production, update the gateway customer's default payment method.
            $this->audit->log(
                event: AuditEventType::PAYMENT_METHOD_SET_DEFAULT->value,
                action: 'set_default',
                subject: null,
                description: "Default payment method set for tenant {$tenantId}: {$methodId}.",
                metadata: ['tenant_id' => $tenantId, 'method_id' => $methodId],
            );
        });
    }

    public function remove(string $tenantId, string $methodId, int $actorId): void
    {
        DB::transaction(function () use ($tenantId, $methodId): void {
            $this->audit->log(
                event: AuditEventType::PAYMENT_METHOD_REMOVED->value,
                action: 'removed',
                subject: null,
                description: "Payment method removed for tenant {$tenantId}: {$methodId}.",
                metadata: ['tenant_id' => $tenantId, 'method_id' => $methodId],
            );
        });
    }

    /**
     * Retrieve 3DS / SCA configuration from platform settings.
     */
    public function get3dsConfig(): array
    {
        $setting = PlatformSetting::first();

        return (array) ($setting?->getValue('3ds_config') ?? [
            'enabled' => false,
            'mode' => 'automatic',
            'exemption_low' => true,
        ]);
    }

    public function update3dsConfig(array $data, int $actorId): array
    {
        return DB::transaction(function () use ($data): array {
            $setting = PlatformSetting::firstOrCreate([]);
            $setting->setValue('3ds_config', $data);
            $setting->save();

            $this->audit->log(
                event: AuditEventType::THREE_DS_CONFIG_UPDATED->value,
                action: 'configured',
                subject: $setting,
                description: '3DS/SCA configuration updated.',
                after: $data,
            );

            return $data;
        });
    }
}
