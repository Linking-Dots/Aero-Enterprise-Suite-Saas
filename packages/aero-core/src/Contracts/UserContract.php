<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * UserContract Interface
 *
 * Defines the contract for user objects across package boundaries.
 * This interface allows modules (like HRM) to depend on user functionality
 * without directly coupling to the User model implementation.
 *
 * Purpose:
 * - Enforce package boundaries (core ← contracts → modules)
 * - Allow independent evolution of User model and dependent modules
 * - Enable dependency injection and testing with mocks
 * - Support clean architecture and DDD principles
 *
 * Usage in Modules:
 * ```php
 * use Aero\Contracts\UserContract;
 *
 * public function __construct(private UserContract $user) {}
 * ```
 */
interface UserContract
{
    /**
     * Get the user's unique identifier.
     */
    public function getId(): int;

    /**
     * Get the user's full name.
     */
    public function getName(): string;

    /**
     * Get the user's email address.
     */
    public function getEmail(): string;

    /**
     * Get the user's phone number (if available).
     */
    public function getPhone(): ?string;

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool;

    /**
     * Check if the user's email has been verified.
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Get the user's profile image URL.
     */
    public function getProfileImageUrl(): ?string;

    /**
     * Get the user's locale/language preference.
     */
    public function getLocale(): string;

    /**
     * Get the user's timezone.
     */
    public function getTimezone(): string;

    /**
     * Send a notification to the user.
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notify($notification);

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool;

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool;

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool;

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool;

    /**
     * Get the user's roles.
     *
     * @return Collection
     */
    public function getRoles();

    /**
     * Get the user's permissions.
     *
     * @return Collection
     */
    public function getPermissions();

    /**
     * Get the date when the user was created.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the date when the user was last updated.
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Get the user's notification preferences for a specific channel.
     *
     * @param  string  $channel  Channel name (email, sms, push, database)
     */
    public function prefersNotificationChannel(string $channel): bool;

    /**
     * Get the value of a dynamically registered relationship.
     *
     * This allows modules to access relationships registered via
     * UserRelationshipRegistry without direct model coupling.
     *
     * Example: $user->getRelationship('employee')
     *
     * @return mixed
     */
    public function getRelationship(string $relationshipName);

    /**
     * Check if a dynamically registered relationship exists.
     */
    public function hasRelationship(string $relationshipName): bool;

    /**
     * Convert the user to an array.
     *
     * @return array
     */
    public function toArray();
}
