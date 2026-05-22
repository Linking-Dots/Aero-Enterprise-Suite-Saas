<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Infra\StatusIncident;
use Aero\Platform\Models\Infra\StatusPageComponent;
use Aero\Platform\Models\Infra\StatusPagePostmortem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StatusPageService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    /** @return Collection<int, StatusPageComponent> */
    public function getComponents(): Collection
    {
        return StatusPageComponent::orderBy('order_index')->get();
    }

    public function createComponent(array $data): StatusPageComponent
    {
        return DB::transaction(function () use ($data): StatusPageComponent {
            $component = StatusPageComponent::create($data);

            $this->audit->log(
                event: AuditEventType::STATUS_COMPONENT_CREATED->value,
                action: 'create',
                subject: $component,
                description: "Status component '{$component->name}' created"
            );

            return $component;
        });
    }

    public function updateComponent(StatusPageComponent $component, array $data): StatusPageComponent
    {
        return DB::transaction(function () use ($component, $data): StatusPageComponent {
            $component->update($data);

            $this->audit->log(
                event: AuditEventType::STATUS_COMPONENT_UPDATED->value,
                action: 'update',
                subject: $component,
                description: "Status component '{$component->name}' updated"
            );

            return $component->refresh();
        });
    }

    public function deleteComponent(StatusPageComponent $component): void
    {
        DB::transaction(function () use ($component): void {
            $name = $component->name;
            $component->delete();

            $this->audit->log(
                event: AuditEventType::STATUS_COMPONENT_DELETED->value,
                action: 'delete',
                subject: null,
                description: "Status component '{$name}' deleted"
            );
        });
    }

    public function setComponentStatus(StatusPageComponent $component, string $status): StatusPageComponent
    {
        return DB::transaction(function () use ($component, $status): StatusPageComponent {
            $component->update(['status' => $status]);

            $this->audit->log(
                event: AuditEventType::STATUS_COMPONENT_STATUS_SET->value,
                action: 'update',
                subject: $component,
                description: "Component '{$component->name}' status set to {$status}"
            );

            return $component->refresh();
        });
    }

    public function createIncident(array $data): StatusIncident
    {
        return DB::transaction(function () use ($data): StatusIncident {
            $incident = StatusIncident::create(array_merge($data, [
                'started_at' => $data['started_at'] ?? now(),
            ]));

            $this->audit->log(
                event: AuditEventType::STATUS_INCIDENT_CREATED->value,
                action: 'create',
                subject: $incident,
                description: "Incident '{$incident->title}' created (impact: {$incident->impact})"
            );

            return $incident;
        });
    }

    public function updateIncident(StatusIncident $incident, array $data): StatusIncident
    {
        return DB::transaction(function () use ($incident, $data): StatusIncident {
            // Append update entry to the updates JSON log
            $updates = $incident->updates ?? [];
            if (isset($data['update_message'])) {
                $updates[] = [
                    'message' => $data['update_message'],
                    'status' => $data['status'] ?? $incident->status,
                    'at' => now()->toISOString(),
                ];
                unset($data['update_message']);
            }
            $data['updates'] = $updates;

            $incident->update($data);

            $this->audit->log(
                event: AuditEventType::STATUS_INCIDENT_UPDATED->value,
                action: 'update',
                subject: $incident,
                description: "Incident '{$incident->title}' updated"
            );

            return $incident->refresh();
        });
    }

    public function resolveIncident(StatusIncident $incident): StatusIncident
    {
        return DB::transaction(function () use ($incident): StatusIncident {
            $incident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::STATUS_INCIDENT_RESOLVED->value,
                action: 'resolve',
                subject: $incident,
                description: "Incident '{$incident->title}' resolved"
            );

            return $incident->refresh();
        });
    }

    public function createPostmortem(StatusIncident $incident, array $data): StatusPagePostmortem
    {
        return DB::transaction(function () use ($incident, $data): StatusPagePostmortem {
            $postmortem = StatusPagePostmortem::create(array_merge($data, [
                'incident_id' => $incident->id,
            ]));

            $this->audit->log(
                event: AuditEventType::STATUS_POSTMORTEM_CREATED->value,
                action: 'create',
                subject: $postmortem,
                description: "Postmortem created for incident '{$incident->title}'"
            );

            return $postmortem;
        });
    }

    public function publishPostmortem(StatusPagePostmortem $postmortem): StatusPagePostmortem
    {
        return DB::transaction(function () use ($postmortem): StatusPagePostmortem {
            $postmortem->update(['published_at' => now()]);

            $this->audit->log(
                event: AuditEventType::STATUS_POSTMORTEM_PUBLISHED->value,
                action: 'publish',
                subject: $postmortem,
                description: "Postmortem '{$postmortem->title}' published"
            );

            return $postmortem->refresh();
        });
    }
}
