<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface DomainEventContract
{
    public function getModuleCode(): string;
    public function getSubModuleCode(): string;
    public function getComponentCode(): ?string;
    public function getActionCode(): string;
    public function getActorUserId(): ?int;
    public function getEntityId(): int;
    public function getEntityType(): string;
    public function getAuditMetadata(): array;
    public function getNotificationContext(): array;
    public function shouldNotify(): bool;
    public function getTimestamp(): \DateTimeInterface;
}
