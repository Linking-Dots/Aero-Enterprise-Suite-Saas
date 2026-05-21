<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Integrations;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Jobs\SendTenantEmailBlastJob;
use Aero\Platform\Models\MaintenanceWindow;
use Aero\Platform\Models\TenantBroadcast;
use Aero\Platform\Models\TenantEmailBlast;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * P-7 — Tenant Communication Service.
 *
 * Manages in-app broadcasts, targeted email blasts, and maintenance windows.
 */
class TenantCommunicationService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    // -------------------------------------------------------------------------
    // Broadcasts
    // -------------------------------------------------------------------------

    public function listBroadcasts(array $filters = []): LengthAwarePaginator
    {
        return TenantBroadcast::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when(isset($filters['published']), fn ($q) => $q->whereNotNull('published_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function createBroadcast(array $data): TenantBroadcast
    {
        return DB::transaction(function () use ($data): TenantBroadcast {
            $broadcast = TenantBroadcast::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'target' => $data['target'] ?? 'all',
                'target_tenant_ids' => $data['target_tenant_ids'] ?? null,
                'created_by' => $data['created_by'] ?? auth('landlord')->id(),
            ]);

            $this->audit->log(
                event: AuditEventType::BROADCAST_CREATED->value,
                action: 'create',
                subject: $broadcast,
                description: "Broadcast created: {$broadcast->title}",
            );

            return $broadcast;
        });
    }

    public function publishBroadcast(int|string $id): TenantBroadcast
    {
        return DB::transaction(function () use ($id): TenantBroadcast {
            $broadcast = TenantBroadcast::findOrFail($id);
            $broadcast->update(['published_at' => now()]);

            $this->audit->log(
                event: AuditEventType::BROADCAST_PUBLISHED->value,
                action: 'publish',
                subject: $broadcast,
                description: "Broadcast published: {$broadcast->title}",
            );

            return $broadcast->refresh();
        });
    }

    public function dismissAll(int|string $id): TenantBroadcast
    {
        return DB::transaction(function () use ($id): TenantBroadcast {
            $broadcast = TenantBroadcast::findOrFail($id);
            // Soft-delete represents dismiss-all (removes from all tenant inboxes)
            $broadcast->delete();

            $this->audit->log(
                event: AuditEventType::BROADCAST_DISMISSED_ALL->value,
                action: 'dismiss-all',
                subject: $broadcast,
                description: "Broadcast dismissed for all tenants: {$broadcast->title}",
            );

            return $broadcast;
        });
    }

    // -------------------------------------------------------------------------
    // Email Blasts
    // -------------------------------------------------------------------------

    public function listEmailBlasts(array $filters = []): LengthAwarePaginator
    {
        return TenantEmailBlast::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('subject', 'like', "%{$v}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function createEmailBlast(array $data): TenantEmailBlast
    {
        return DB::transaction(function () use ($data): TenantEmailBlast {
            $blast = TenantEmailBlast::create([
                'subject' => $data['subject'],
                'body_html' => $data['body_html'],
                'target_filter' => $data['target_filter'] ?? null,
                'created_by' => $data['created_by'] ?? auth('landlord')->id(),
            ]);

            $this->audit->log(
                event: AuditEventType::EMAIL_BLAST_CREATED->value,
                action: 'create',
                subject: $blast,
                description: "Email blast created: {$blast->subject}",
            );

            return $blast;
        });
    }

    /**
     * Send an email blast. Dispatches a queued job per matched tenant.
     * Job dispatch is fire-and-forget — the count update happens in the job.
     */
    public function sendBlast(int|string $id): TenantEmailBlast
    {
        return DB::transaction(function () use ($id): TenantEmailBlast {
            $blast = TenantEmailBlast::findOrFail($id);

            if ($blast->isSent()) {
                return $blast;
            }

            $blast->update(['sent_at' => now()]);

            // Dispatch queued job (class created separately by infrastructure)
            if (class_exists(SendTenantEmailBlastJob::class)) {
                SendTenantEmailBlastJob::dispatch($blast);
            }

            $this->audit->log(
                event: AuditEventType::EMAIL_BLAST_SENT->value,
                action: 'send',
                subject: $blast,
                description: "Email blast queued for delivery: {$blast->subject}",
            );

            return $blast->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Maintenance Windows
    // -------------------------------------------------------------------------

    public function listMaintenanceWindows(array $filters = []): LengthAwarePaginator
    {
        return MaintenanceWindow::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function createMaintenanceWindow(array $data): MaintenanceWindow
    {
        return DB::transaction(function () use ($data): MaintenanceWindow {
            $window = MaintenanceWindow::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => MaintenanceWindow::STATUS_SCHEDULED,
                'affected_tenants' => $data['affected_tenants'] ?? 'all',
                'affected_tenant_ids' => $data['affected_tenant_ids'] ?? null,
            ]);

            $this->audit->log(
                event: AuditEventType::MAINTENANCE_WINDOW_SCHEDULED->value,
                action: 'schedule',
                subject: $window,
                description: "Maintenance window scheduled: {$window->title}",
            );

            return $window;
        });
    }

    public function cancelMaintenanceWindow(int|string $id): MaintenanceWindow
    {
        return DB::transaction(function () use ($id): MaintenanceWindow {
            $window = MaintenanceWindow::findOrFail($id);
            $window->update([
                'status' => MaintenanceWindow::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::MAINTENANCE_WINDOW_CANCELLED->value,
                action: 'cancel',
                subject: $window,
                description: "Maintenance window cancelled: {$window->title}",
            );

            return $window->refresh();
        });
    }
}
