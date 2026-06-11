<?php

namespace Aero\Core\Services;

use Closure;

/**
 * User Relationship Registry
 *
 * Allows modules to register relationships and scopes on the User model
 * at runtime, enabling true module independence.
 *
 * Usage in module provider:
 *   $registry = app(UserRelationshipRegistry::class);
 *   $registry->registerRelationship('employee', fn($user) => $user->hasOne(Employee::class));
 */
class UserRelationshipRegistry
{
    /**
     * Registered relationship callbacks.
     *
     * @var array<string, Closure>
     */
    protected array $relationships = [];

    /**
     * Registered scope callbacks.
     *
     * @var array<string, Closure>
     */
    protected array $scopes = [];

    /**
     * Registered attribute accessors.
     *
     * @var array<string, Closure>
     */
    protected array $accessors = [];

    /**
     * Register a dynamic relationship.
     *
     * @param  string  $name  Relationship method name
     * @param  Closure  $callback  Callback that receives the User model and returns a Relation
     */
    public function registerRelationship(string $name, Closure $callback): void
    {
        $this->relationships[$name] = $callback;
    }

    /**
     * Register a dynamic scope.
     *
     * @param  string  $name  Scope method name (without 'scope' prefix)
     * @param  Closure  $callback  Callback that receives query builder
     */
    public function registerScope(string $name, Closure $callback): void
    {
        $this->scopes[$name] = $callback;
    }

    /**
     * Register a dynamic accessor.
     *
     * @param  string  $name  Attribute name
     * @param  Closure  $callback  Callback that receives the User model
     */
    public function registerAccessor(string $name, Closure $callback): void
    {
        $this->accessors[$name] = $callback;
    }

    /**
     * Get a registered relationship callback.
     */
    public function getRelationship(string $name): ?Closure
    {
        return $this->relationships[$name] ?? null;
    }

    /**
     * Get a registered scope callback.
     */
    public function getScope(string $name): ?Closure
    {
        return $this->scopes[$name] ?? null;
    }

    /**
     * Get a registered accessor callback.
     */
    public function getAccessor(string $name): ?Closure
    {
        return $this->accessors[$name] ?? null;
    }

    /**
     * Check if a relationship is registered.
     */
    public function hasRelationship(string $name): bool
    {
        return isset($this->relationships[$name]);
    }

    /**
     * Check if a scope is registered.
     */
    public function hasScope(string $name): bool
    {
        return isset($this->scopes[$name]);
    }

    /**
     * Check if an accessor is registered.
     */
    public function hasAccessor(string $name): bool
    {
        return isset($this->accessors[$name]);
    }

    /**
     * Get all registered relationship names.
     */
    public function getRelationshipNames(): array
    {
        return array_keys($this->relationships);
    }

    /**
     * Get all registered scope names.
     */
    public function getScopeNames(): array
    {
        return array_keys($this->scopes);
    }

    /**
     * Get all registered accessor names.
     */
    public function getAccessorNames(): array
    {
        return array_keys($this->accessors);
    }

    /**
     * Unregister a relationship (for testing).
     */
    public function unregisterRelationship(string $name): void
    {
        unset($this->relationships[$name]);
    }

    /**
     * Clear all registrations (for testing).
     */
    public function clear(): void
    {
        $this->relationships = [];
        $this->scopes = [];
        $this->accessors = [];
    }
}
