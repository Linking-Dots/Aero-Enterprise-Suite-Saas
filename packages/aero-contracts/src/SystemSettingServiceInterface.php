<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Contract for the system-settings service.
 *
 * aero-core provides the concrete SystemSettingService implementation (which also owns
 * the media-bearing SystemSetting model and a richer update() surface). Shared/feature
 * packages depend ONLY on this interface — the model-agnostic read/write surface — so
 * they never name aero-core internals.
 */
interface SystemSettingServiceInterface
{
    /**
     * All settings as a flat associative array (suitable for Inertia props).
     *
     * @return array<string, mixed>
     */
    public function allAsArray(): array;

    /**
     * Get a single setting value by flat key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a single setting value by flat key.
     */
    public function set(string $key, mixed $value): void;
}
