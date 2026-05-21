<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ModuleAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(): Collection
    {
        return Module::orderBy('name')->get();
    }

    /**
     * Toggle a module's catalog availability.
     *
     * ARCH NOTE: Toggles catalog availability only. Does NOT touch existing
     * ProductSubscription rows — tenants currently subscribed keep access until
     * their ProductSubscription is cancelled via the Subscriptions admin (P-2).
     * Setting is_active=false removes the module from the storefront catalog only.
     */
    public function toggleActive(Module $module): Module
    {
        return DB::transaction(function () use ($module) {
            $module->update(['is_active' => ! $module->is_active]);

            $this->audit->log(
                event: AuditEventType::MODULE_TOGGLED->value,
                action: 'toggle-active',
                subject: $module,
                description: "Module {$module->code} set to ".($module->is_active ? 'active' : 'inactive'),
            );

            return $module->refresh();
        });
    }

    public function configure(Module $module, array $config): Module
    {
        return DB::transaction(function () use ($module, $config) {
            $module->update(['config' => $config]);

            $this->audit->log(
                event: AuditEventType::MODULE_CONFIGURED->value,
                action: 'configure',
                subject: $module,
                description: "Module {$module->code} configured",
            );

            return $module->refresh();
        });
    }

    public function updatePricing(Module $module, float $monthly, float $annual): Module
    {
        return DB::transaction(function () use ($module, $monthly, $annual) {
            $module->update([
                'price_monthly' => $monthly,
                'price_annual' => $annual,
            ]);

            $this->audit->log(
                event: 'platform.modules.pricing_updated',
                action: 'edit',
                subject: $module,
                description: "Module {$module->code} pricing: monthly={$monthly}, annual={$annual}",
            );

            return $module->refresh();
        });
    }
}
