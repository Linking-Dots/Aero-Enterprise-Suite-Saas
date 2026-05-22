<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Jobs\Infra\RestoreBackupJob;
use Aero\Platform\Jobs\Infra\RunBackupJob;
use Aero\Platform\Models\Infra\BackupRecord;
use Aero\Platform\Models\Infra\BackupSchedule;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BackupService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function createSchedule(array $data): BackupSchedule
    {
        return DB::transaction(function () use ($data): BackupSchedule {
            $schedule = BackupSchedule::create($data);

            $this->audit->log(
                event: AuditEventType::BACKUP_SCHEDULE_CREATED->value,
                action: 'create',
                subject: $schedule,
                description: "Backup schedule created (frequency: {$schedule->frequency})"
            );

            return $schedule;
        });
    }

    public function updateSchedule(BackupSchedule $schedule, array $data): BackupSchedule
    {
        return DB::transaction(function () use ($schedule, $data): BackupSchedule {
            $schedule->update($data);

            $this->audit->log(
                event: AuditEventType::BACKUP_SCHEDULE_UPDATED->value,
                action: 'update',
                subject: $schedule,
                description: "Backup schedule {$schedule->id} updated"
            );

            return $schedule->refresh();
        });
    }

    public function deleteSchedule(BackupSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void {
            $scheduleId = $schedule->id;
            $schedule->delete();

            $this->audit->log(
                event: AuditEventType::BACKUP_SCHEDULE_DELETED->value,
                action: 'delete',
                subject: null,
                description: "Backup schedule {$scheduleId} deleted"
            );
        });
    }

    public function runManual(string $tenantId, string $storageBackend): BackupRecord
    {
        return DB::transaction(function () use ($tenantId, $storageBackend): BackupRecord {
            $record = BackupRecord::create([
                'tenant_id' => $tenantId,
                'schedule_id' => null,
                'size_bytes' => 0,
                'storage_path' => '',
                'storage_backend' => $storageBackend,
                'status' => 'running',
            ]);

            RunBackupJob::dispatch($record->id);

            $this->audit->log(
                event: AuditEventType::BACKUP_MANUAL_TRIGGERED->value,
                action: 'create',
                subject: $record,
                description: "Manual backup triggered for tenant {$tenantId}"
            );

            return $record;
        });
    }

    public function restore(BackupRecord $backup, string $targetTenantId): void
    {
        DB::transaction(function () use ($backup, $targetTenantId): void {
            RestoreBackupJob::dispatch($backup->id, $targetTenantId);

            $this->audit->log(
                event: AuditEventType::BACKUP_RESTORE_INITIATED->value,
                action: 'restore',
                subject: $backup,
                description: "Restore of backup {$backup->id} initiated for tenant {$targetTenantId}"
            );
        });
    }

    /** @return array<string, mixed> */
    public function listRestorePoints(string $tenantId): array
    {
        return BackupRecord::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get(['id', 'storage_path', 'size_bytes', 'storage_backend', 'completed_at'])
            ->toArray();
    }

    public function configureStorage(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->audit->log(
                event: AuditEventType::BACKUP_STORAGE_CONFIGURED->value,
                action: 'configure',
                subject: null,
                description: "Backup storage configured: {$data['backend_code']}"
            );
        });
    }

    public function computeNextRunAt(BackupSchedule $schedule): Carbon
    {
        [$hour, $minute] = explode(':', $schedule->time_of_day);
        $base = now()->setTime((int) $hour, (int) $minute, 0);

        if ($base->isPast()) {
            $base = match ($schedule->frequency) {
                'daily' => $base->addDay(),
                'weekly' => $base->addWeek(),
                'monthly' => $base->addMonth(),
                default => $base->addDay(),
            };
        }

        return $base;
    }

    public function paginateSchedules(int $perPage = 20): LengthAwarePaginator
    {
        return BackupSchedule::orderByDesc('created_at')->paginate($perPage);
    }
}
