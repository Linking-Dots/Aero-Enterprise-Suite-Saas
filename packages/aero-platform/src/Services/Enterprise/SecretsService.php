<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\KmsKey;
use Aero\Platform\Models\Enterprise\TenantDek;
use Aero\Platform\Models\Enterprise\VaultSecret;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SecretsService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function rotateMasterKey(KmsKey $kmsKey, string $rotatedBy): KmsKey
    {
        return DB::transaction(function () use ($kmsKey, $rotatedBy): KmsKey {
            $kmsKey->update([
                'rotated_at' => now(),
                'rotated_by' => $rotatedBy,
            ]);

            $this->audit->log(
                event: AuditEventType::KMS_KEY_ROTATED->value,
                action: 'rotate',
                subject: $kmsKey,
                description: "KMS master key {$kmsKey->key_id} rotated by {$rotatedBy}"
            );

            return $kmsKey->refresh();
        });
    }

    public function rotateTenantDek(TenantDek $dek, string $rotatedBy): TenantDek
    {
        return DB::transaction(function () use ($dek, $rotatedBy): TenantDek {
            $dek->update(['rotated_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_DEK_ROTATED->value,
                action: 'rotate',
                subject: $dek,
                description: "DEK for tenant {$dek->tenant_id} rotated by {$rotatedBy}"
            );

            return $dek->refresh();
        });
    }

    public function storeSecret(array $data, string $createdBy): VaultSecret
    {
        return DB::transaction(function () use ($data, $createdBy): VaultSecret {
            $secret = VaultSecret::create([
                ...$data,
                'created_by' => $createdBy,
            ]);

            $this->audit->log(
                event: AuditEventType::VAULT_SECRET_STORED->value,
                action: 'create',
                subject: $secret,
                description: "Vault secret '{$secret->name}' stored"
            );

            return $secret;
        });
    }

    public function revokeSecret(VaultSecret $secret, string $revokedBy): void
    {
        DB::transaction(function () use ($secret, $revokedBy): void {
            $secret->update(['revoked_at' => now()]);

            $this->audit->log(
                event: AuditEventType::VAULT_SECRET_REVOKED->value,
                action: 'revoke',
                subject: $secret,
                description: "Vault secret '{$secret->name}' revoked by {$revokedBy}"
            );
        });
    }

    public function getAuditLog(int $perPage = 50): LengthAwarePaginator
    {
        return KmsKey::orderByDesc('rotated_at')->paginate($perPage);
    }
}
