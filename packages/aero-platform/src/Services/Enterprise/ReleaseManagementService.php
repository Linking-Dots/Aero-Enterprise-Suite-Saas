<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\ChangelogEntry;
use Aero\Platform\Models\Enterprise\ReleaseVersion;
use Aero\Platform\Models\Enterprise\TenantRollout;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReleaseManagementService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function publishVersion(array $data, string $publishedBy): ReleaseVersion
    {
        return DB::transaction(function () use ($data, $publishedBy): ReleaseVersion {
            $version = ReleaseVersion::create([
                ...$data,
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $publishedBy,
            ]);

            $this->audit->log(
                event: AuditEventType::RELEASE_VERSION_PUBLISHED->value,
                action: 'publish',
                subject: $version,
                description: "Release version {$version->version} published by {$publishedBy}"
            );

            return $version;
        });
    }

    public function rolloutToTenants(ReleaseVersion $version, array $tenantIds, string $initiatedBy): void
    {
        DB::transaction(function () use ($version, $tenantIds, $initiatedBy): void {
            foreach ($tenantIds as $tenantId) {
                TenantRollout::create([
                    'version_id' => $version->id,
                    'tenant_id' => $tenantId,
                    'status' => 'pending',
                ]);
            }

            $this->audit->log(
                event: AuditEventType::RELEASE_ROLLOUT_INITIATED->value,
                action: 'rollout',
                subject: $version,
                description: "Version {$version->version} rollout initiated for " . count($tenantIds) . " tenants by {$initiatedBy}"
            );
        });
    }

    public function rollback(TenantRollout $rollout, string $rolledBackBy): void
    {
        DB::transaction(function () use ($rollout, $rolledBackBy): void {
            $rollout->update(['status' => 'rolled_back', 'rolled_back_at' => now()]);

            $this->audit->log(
                event: AuditEventType::RELEASE_ROLLED_BACK->value,
                action: 'rollback',
                subject: $rollout,
                description: "Rollout {$rollout->id} rolled back by {$rolledBackBy}"
            );
        });
    }

    public function publishChangelog(array $data): ChangelogEntry
    {
        return DB::transaction(function () use ($data): ChangelogEntry {
            $entry = ChangelogEntry::create([
                ...$data,
                'is_published' => true,
                'published_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::CHANGELOG_PUBLISHED->value,
                action: 'publish',
                subject: $entry,
                description: "Changelog entry '{$entry->title}' published"
            );

            return $entry;
        });
    }

    public function listVersions(int $perPage = 25): LengthAwarePaginator
    {
        return ReleaseVersion::orderByDesc('published_at')->paginate($perPage);
    }
}
