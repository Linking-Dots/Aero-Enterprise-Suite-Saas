<?php

declare(strict_types=1);

namespace Aero\Project\Events;

use Aero\Contracts\DomainEventContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Project Domain Event
 *
 * All Project events should extend this class to ensure:
 * - HRMAC-aware notification routing
 * - Consistent audit logging
 * - Proper event metadata
 *
 * ARCHITECTURAL RULE: Project events use user_id only.
 * NO references to HRM Employee model or Core User model directly.
 * Cross-package data resolution is done via contracts.
 *
 * Events are immutable and auditable.
 */
abstract class BaseProjectEvent implements DomainEventContract
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected \DateTimeInterface $timestamp;

    protected ?int $actorUserId;

    protected array $metadata;

    public function __construct(?int $actorUserId = null, array $metadata = [])
    {
        $this->actorUserId = $actorUserId;
        $this->metadata = $metadata;
        $this->timestamp = now();
    }

    public function getModuleCode(): string
    {
        return 'project';
    }

    abstract public function getSubModuleCode(): string;

    public function getComponentCode(): ?string
    {
        return null;
    }

    abstract public function getActionCode(): string;

    /**
     * Get the user ID who triggered this event.
     */
    public function getActorUserId(): ?int
    {
        return $this->actorUserId;
    }

    abstract public function getEntityId(): int;

    abstract public function getEntityType(): string;

    public function getAuditMetadata(): array
    {
        return array_merge([
            'module' => $this->getModuleCode(),
            'sub_module' => $this->getSubModuleCode(),
            'component' => $this->getComponentCode(),
            'action' => $this->getActionCode(),
            'entity_type' => $this->getEntityType(),
            'entity_id' => $this->getEntityId(),
            'actor_user_id' => $this->getActorUserId(),
            'timestamp' => $this->getTimestamp()->toIso8601String(),
        ], $this->metadata);
    }

    public function getNotificationContext(): array
    {
        return [
            'entity_id' => $this->getEntityId(),
            'entity_type' => $this->getEntityType(),
            'actor_user_id' => $this->getActorUserId(),
        ];
    }

    public function shouldNotify(): bool
    {
        return true;
    }

    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp;
    }

    /**
     * Get project-specific context for HRMAC notification routing.
     * Includes project_id for scoped notifications.
     */
    abstract public function getProjectId(): int;
}
