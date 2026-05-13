<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface UserContract
{
    public function getId(): int;
    public function getName(): string;
    public function getEmail(): string;
    public function getPhone(): ?string;
    public function isActive(): bool;
    public function hasVerifiedEmail(): bool;
    public function getProfileImageUrl(): ?string;
    public function getLocale(): string;
    public function getTimezone(): string;
    public function notify($notification);
    public function hasPermission(string $permission): bool;
    public function hasAnyPermission(array $permissions): bool;
    public function hasAllPermissions(array $permissions): bool;
    public function hasRole(string $role): bool;
    public function getRoles();
    public function getPermissions();
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
    public function prefersNotificationChannel(string $channel): bool;
    public function getRelationship(string $relationshipName);
    public function hasRelationship(string $relationshipName): bool;
    public function toArray();
}
