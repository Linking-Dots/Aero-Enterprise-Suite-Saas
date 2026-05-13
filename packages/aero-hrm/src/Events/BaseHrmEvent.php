<?php

declare(strict_types=1);

namespace Aero\HRM\Events;

use Aero\Contracts\DomainEventContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base HRM Domain Event
 *
 * All HRM events should extend this class to ensure:
 * - HRMAC-aware notification routing
 * - Consistent audit logging
 * - Proper event metadata
 *
 * ARCHITECTURAL RULE: HRM events use employee_id, never user_id.
 * The Core notification layer resolves employee_id → user_id via EmployeeServiceContract.
 *
 * Events are immutable and auditable.
 */
abstract class BaseHrmEvent implements DomainEventContract
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected \DateTimeInterface $timestamp;

    protected ?int $actorEmployeeId;

    protected array $metadata;

    public function __construct(?int $actorEmployeeId = null, array $metadata = [])
    {
        $this->actorEmployeeId = $actorEmployeeId;
        $this->metadata = $metadata;
        $this->timestamp = now();
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    abstract public function getSubModuleCode(): string;

    public function getComponentCode(): ?string
    {
        return null;
    }

    abstract public function getActionCode(): string;

    /**
     * Get the employee ID who triggered this event.
     * Note: This is employee_id, not user_id. Core layer resolves to user.
     */
    public function getActorUserId(): ?int
    {
        // Returns employee_id - Core layer maps to user_id via contract
        return $this->actorEmployeeId;
    }

    /**
     * Get the actor employee ID directly.
     */
    public function getActorEmployeeId(): ?int
    {
        return $this->actorEmployeeId;
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
            'actor_employee_id' => $this->getActorEmployeeId(),
            'timestamp' => $this->getTimestamp()->toIso8601String(),
        ], $this->metadata);
    }

    public function getNotificationContext(): array
    {
        return [
            'entity_id' => $this->getEntityId(),
            'entity_type' => $this->getEntityType(),
            'actor_employee_id' => $this->getActorEmployeeId(),
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
     * Get additional metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
