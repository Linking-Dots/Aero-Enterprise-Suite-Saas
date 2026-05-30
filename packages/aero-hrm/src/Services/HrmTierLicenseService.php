<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

/**
 * HRM Push H.T3 — per-submodule licensing tier resolution.
 *
 * Phase 1 audit found HRM only had module-level `min_plan` => 'basic' —
 * impossible to tier as Basic/Pro/Enterprise. This service reads the
 * tier_licensing map from config/module.php and answers:
 *   - getRequiredTier($submoduleCode): which tier is needed?
 *   - meetsRequirement($currentTier, $submoduleCode): does this plan satisfy?
 *
 * Tier ordering is intentionally lexical-via-rank (not alphabetic):
 *   free < basic < professional < enterprise
 *
 * Companion middleware HrmTierLicenseMiddleware enforces this at request
 * time. Operators override by publishing config/aero-hrm-licensing.php
 * (takes precedence over the package config).
 */
class HrmTierLicenseService
{
    /**
     * Tier rank — higher = more permissive. A tenant on rank N can access
     * any submodule with required-rank <= N.
     */
    public const TIER_RANK = [
        'free'         => 0,
        'basic'        => 10,
        'professional' => 20,
        'enterprise'   => 30,
    ];

    /** Default tier when a submodule has no explicit mapping. */
    public const DEFAULT_TIER = 'basic';

    /**
     * Get the minimum plan tier required to access a submodule.
     */
    public function getRequiredTier(string $submoduleCode): string
    {
        // Host-level override takes precedence (only if Laravel container is booted)
        try {
            $override = function_exists('config') ? config("aero-hrm-licensing.{$submoduleCode}") : null;
            if (is_string($override) && isset(self::TIER_RANK[$override])) {
                return $override;
            }
        } catch (\Throwable) {
            // No container — fall through to package config
        }

        // Package config — direct file load (works without booted Laravel)
        $packageMap = $this->loadPackageTierMap();

        return $packageMap[$submoduleCode] ?? self::DEFAULT_TIER;
    }

    /**
     * Check whether a tenant on $currentTier can access $submoduleCode.
     */
    public function meetsRequirement(?string $currentTier, string $submoduleCode): bool
    {
        if ($currentTier === null) {
            // No tenant tier known — fail closed by default. Operators can
            // enable fail-open via config('aero-hrm-licensing.fail_open_on_unknown_tier').
            try {
                if (function_exists('config')) {
                    return (bool) config('aero-hrm-licensing.fail_open_on_unknown_tier', false);
                }
            } catch (\Throwable) {
                // No container — fail closed
            }
            return false;
        }

        $currentRank = self::TIER_RANK[$currentTier] ?? -1;
        $requiredTier = $this->getRequiredTier($submoduleCode);
        $requiredRank = self::TIER_RANK[$requiredTier] ?? PHP_INT_MAX;

        return $currentRank >= $requiredRank;
    }

    /**
     * Load the tier_licensing array directly from the package module.php
     * (used when config() can't reach it — e.g. before configs are merged
     * or in tests bypassing the service container).
     */
    private function loadPackageTierMap(): array
    {
        $path = dirname(__DIR__, 2).'/config/module.php';
        if (! is_file($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config['tier_licensing'] ?? null) ? $config['tier_licensing'] : [];
    }
}
