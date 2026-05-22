<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\LicenseActivation;
use Aero\Platform\Models\Enterprise\LicenseKey;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LicenseService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    /**
     * Generate a new license key.
     *
     * @param  array  $productCodes  Required — list of entitled product codes (e.g. ['hrm','finance']).
     * @param  string|null  $planCode  Optional pricing tier label (metadata only, no access grants).
     * @param  array  $issuedTo  ['name' => ..., 'email' => ...]
     * @param  int  $maxActivations  Maximum number of concurrent activations.
     */
    public function generate(
        array $productCodes,
        ?string $planCode,
        array $issuedTo,
        int $maxActivations = 1,
        ?string $createdBy = null,
        ?\DateTimeInterface $expiresAt = null
    ): LicenseKey {
        if (empty($productCodes)) {
            throw new \InvalidArgumentException('At least one product code is required to generate a license key.');
        }

        return DB::transaction(function () use ($productCodes, $planCode, $issuedTo, $maxActivations, $createdBy, $expiresAt): LicenseKey {
            $rawKey = strtoupper(Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8));
            $hashedKey = hash('sha256', $rawKey);

            $license = LicenseKey::create([
                'key' => $hashedKey,
                'product_codes' => $productCodes,
                'plan_code' => $planCode,
                'max_activations' => $maxActivations,
                'activation_count' => 0,
                'issued_to_name' => $issuedTo['name'],
                'issued_to_email' => $issuedTo['email'],
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ]);

            $this->audit->log(
                event: AuditEventType::LICENSE_KEY_GENERATED->value,
                action: 'generate',
                subject: $license,
                description: "License key generated for {$issuedTo['email']} — products: " . implode(', ', $productCodes)
            );

            // Return a transient copy with the raw key for display (not stored)
            $license->key = $rawKey;

            return $license;
        });
    }

    public function revoke(LicenseKey $license, string $revokedBy): LicenseKey
    {
        return DB::transaction(function () use ($license, $revokedBy): LicenseKey {
            $license->update(['revoked_at' => now()]);

            $this->audit->log(
                event: AuditEventType::LICENSE_KEY_REVOKED->value,
                action: 'revoke',
                subject: $license,
                description: "License key {$license->id} revoked by {$revokedBy}"
            );

            return $license->refresh();
        });
    }

    public function extend(LicenseKey $license, \DateTimeInterface $newExpiresAt, string $extendedBy): LicenseKey
    {
        return DB::transaction(function () use ($license, $newExpiresAt, $extendedBy): LicenseKey {
            $license->update(['expires_at' => $newExpiresAt]);

            $this->audit->log(
                event: AuditEventType::LICENSE_KEY_EXTENDED->value,
                action: 'extend',
                subject: $license,
                description: "License key {$license->id} extended to {$newExpiresAt->format('Y-m-d')} by {$extendedBy}"
            );

            return $license->refresh();
        });
    }

    public function activate(LicenseKey $license, array $activationData): LicenseActivation
    {
        return DB::transaction(function () use ($license, $activationData): LicenseActivation {
            if (! $license->canActivate()) {
                throw new RuntimeException(
                    "License {$license->id} cannot be activated: max activations ({$license->max_activations}) reached or key is revoked/expired."
                );
            }

            $activation = LicenseActivation::create([
                'license_id' => $license->id,
                'install_id' => $activationData['install_id'],
                'domain' => $activationData['domain'] ?? null,
                'ip_address' => $activationData['ip_address'] ?? null,
                'activated_at' => now(),
                'last_ping_at' => now(),
            ]);

            $license->increment('activation_count');

            $this->audit->log(
                event: AuditEventType::LICENSE_ACTIVATED->value,
                action: 'activate',
                subject: $activation,
                description: "License {$license->id} activated on install {$activationData['install_id']}"
            );

            return $activation;
        });
    }

    public function deactivate(LicenseActivation $activation, string $deactivatedBy): void
    {
        DB::transaction(function () use ($activation, $deactivatedBy): void {
            $activation->update(['deactivated_at' => now()]);
            $activation->licenseKey()->decrement('activation_count');

            $this->audit->log(
                event: AuditEventType::LICENSE_DEACTIVATED->value,
                action: 'deactivate',
                subject: $activation,
                description: "License activation {$activation->id} deactivated by {$deactivatedBy}"
            );
        });
    }

    public function listActivations(LicenseKey $license): \Illuminate\Database\Eloquent\Collection
    {
        return $license->activations()->orderByDesc('activated_at')->get();
    }

    public function index(int $perPage = 25): LengthAwarePaginator
    {
        return LicenseKey::withCount('activations')->orderByDesc('created_at')->paginate($perPage);
    }
}
