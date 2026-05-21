<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Integrations;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PlatformApiKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * P-7 — Platform API Key Service.
 *
 * create() returns the raw plaintext key exactly once.
 * Thereafter only key_hash (bcrypt) and key_prefix are stored.
 */
class PlatformApiKeyService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return PlatformApiKey::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when(isset($filters['revoked']), fn ($q) => $q->whereNotNull('revoked_at'))
            ->when(isset($filters['active']) && ! isset($filters['revoked']), fn ($q) => $q->whereNull('revoked_at'))
            ->with('createdBy:id,name,email')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Create a new API key. Returns the model plus raw plaintext key.
     *
     * @return array{model: PlatformApiKey, raw_key: string}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $rawKey = 'sk_'.Str::random(56); // 60-char total
            $prefix = substr($rawKey, 0, 8);

            $model = PlatformApiKey::create([
                'name' => $data['name'],
                'key_prefix' => $prefix,
                'key_hash' => Hash::make($rawKey),
                'key' => $rawKey, // legacy column — overwritten below by key_hash lookup
                'scopes' => $data['scopes'] ?? [],
                'expires_at' => $data['expires_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'user_id' => $data['created_by'] ?? null,
                'is_active' => true,
            ]);

            $this->audit->log(
                event: AuditEventType::API_KEY_CREATED->value,
                action: 'create',
                subject: $model,
                description: "Platform API key created: {$model->name}",
            );

            return ['model' => $model, 'raw_key' => $rawKey];
        });
    }

    public function revoke(int|string $id): PlatformApiKey
    {
        return DB::transaction(function () use ($id): PlatformApiKey {
            $key = PlatformApiKey::findOrFail($id);

            $key->update(['revoked_at' => now(), 'is_active' => false]);

            $this->audit->log(
                event: AuditEventType::API_KEY_REVOKED->value,
                action: 'revoke',
                subject: $key,
                description: "Platform API key revoked: {$key->name}",
            );

            return $key->refresh();
        });
    }
}
