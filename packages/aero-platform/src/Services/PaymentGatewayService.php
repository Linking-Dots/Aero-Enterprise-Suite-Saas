<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\AuditService;
use Aero\Platform\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    /**
     * Return all gateway records.
     *
     * @return Collection<int, PaymentGateway>
     */
    public function all(): Collection
    {
        return PaymentGateway::orderBy('id')->get();
    }

    /**
     * Create a gateway record.
     * The 'config' array is encrypted at-rest via EncryptedField cast.
     */
    public function create(array $data): PaymentGateway
    {
        return DB::transaction(function () use ($data) {
            /** @var PaymentGateway $gw */
            $gw = PaymentGateway::create($data);

            $this->audit->log(
                'payment_gateway.created',
                $gw,
                "Payment gateway [{$gw->code}] created."
            );

            return $gw;
        });
    }

    /**
     * Update a gateway's label / enabled state.
     * Config is passed as a plain array — EncryptedField cast handles encryption.
     */
    public function update(PaymentGateway $gw, array $data): PaymentGateway
    {
        return DB::transaction(function () use ($gw, $data) {
            $old = ['code' => $gw->code, 'is_enabled' => $gw->is_enabled, 'is_default' => $gw->is_default];

            $gw->update($data);

            $this->audit->log(
                AuditEventType::PAYMENT_GATEWAY_UPDATED->value,
                $gw,
                "Payment gateway [{$gw->code}] updated.",
                $old,
                ['is_enabled' => $gw->is_enabled, 'is_default' => $gw->is_default]
            );

            return $gw->fresh();
        });
    }

    /**
     * Update only the encrypted config blob for a gateway.
     *
     * @param  array<string, mixed>  $config
     */
    public function updateConfig(PaymentGateway $gw, array $config): PaymentGateway
    {
        return DB::transaction(function () use ($gw, $config) {
            $gw->update(['config' => $config]);

            $this->audit->log(
                'payment_gateway.config_updated',
                $gw,
                "Payment gateway [{$gw->code}] config updated."
            );

            return $gw->fresh();
        });
    }

    /**
     * Toggle enabled state.
     */
    public function toggle(PaymentGateway $gw): PaymentGateway
    {
        return $this->update($gw, ['is_enabled' => ! $gw->is_enabled]);
    }

    /**
     * Set one gateway as default, clearing all others.
     */
    public function setDefault(PaymentGateway $gw): PaymentGateway
    {
        return DB::transaction(function () use ($gw) {
            PaymentGateway::query()->update(['is_default' => false]);
            $gw->update(['is_default' => true, 'is_enabled' => true]);

            $this->audit->log(
                'payment_gateway.set_default',
                $gw,
                "Payment gateway [{$gw->code}] set as default."
            );

            return $gw->fresh();
        });
    }

    /**
     * Delete a gateway record (hard delete — gateways are platform config, not tenant data).
     */
    public function delete(PaymentGateway $gw): void
    {
        DB::transaction(function () use ($gw) {
            $this->audit->log(
                'payment_gateway.deleted',
                $gw,
                "Payment gateway [{$gw->code}] deleted."
            );
            $gw->delete();
        });
    }
}
