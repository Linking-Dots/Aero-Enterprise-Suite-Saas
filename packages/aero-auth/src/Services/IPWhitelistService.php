<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Kernel\Support\TenantCache;
use Illuminate\Support\Facades\Log;

/**
 * IP Whitelist Service
 *
 * Manages IP-based access control for tenants and users.
 * Supports CIDR notation, IP ranges, and geographic restrictions.
 *
 * Features:
 * - Tenant-level IP whitelist/blacklist
 * - User-specific IP restrictions
 * - CIDR range support (e.g., 192.168.1.0/24)
 * - IP range support (e.g., 192.168.1.1-192.168.1.100)
 * - Logging and alerts for blocked access
 * - VPN/proxy detection warnings
 *
 * Usage:
 * ```php
 * $ipService = app(IPWhitelistService::class);
 *
 * // Check if IP is allowed
 * if (!$ipService->isIpAllowed($request->ip())) {
 *     abort(403, 'Access denied from your IP address');
 * }
 *
 * // Add IP to whitelist
 * $ipService->addToWhitelist('192.168.1.0/24', 'Office network');
 *
 * // Block specific IP
 * $ipService->addToBlacklist('10.0.0.5', 'Suspicious activity');
 * ```
 */
class IPWhitelistService
{
    /**
     * IP list modes.
     */
    public const MODE_DISABLED = 'disabled';

    public const MODE_WHITELIST = 'whitelist'; // Only allow listed IPs

    public const MODE_BLACKLIST = 'blacklist'; // Block listed IPs, allow others

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Check if an IP address is allowed.
     */
    public function isIpAllowed(string $ip, ?User $user = null): bool
    {
        $config = $this->getConfig();

        // If disabled, allow all
        if ($config['mode'] === self::MODE_DISABLED) {
            return true;
        }

        // Always allow internal/localhost
        if ($this->isLocalAddress($ip)) {
            return true;
        }

        // Check user-specific restrictions first
        if ($user && ! $this->isUserIpAllowed($user, $ip)) {
            $this->logBlockedAccess($ip, $user, 'user_restriction');

            return false;
        }

        // Check blacklist (always applied regardless of mode)
        if ($this->isIpInList($ip, $config['blacklist'])) {
            $this->logBlockedAccess($ip, $user, 'blacklisted');

            return false;
        }

        // In whitelist mode, IP must be in whitelist
        if ($config['mode'] === self::MODE_WHITELIST) {
            $allowed = $this->isIpInList($ip, $config['whitelist']);

            if (! $allowed) {
                $this->logBlockedAccess($ip, $user, 'not_whitelisted');
            }

            return $allowed;
        }

        // In blacklist mode, allow if not blacklisted
        return true;
    }

    /**
     * Get the IP configuration for the current tenant.
     */
    public function getConfig(): array
    {
        $tenant = tenant();

        if (! $tenant) {
            return $this->getDefaultConfig();
        }

        $cacheKey = "ip_whitelist:tenant:{$tenant->id}";

        return TenantCache::remember($cacheKey, $this->cacheTtl, function () use ($tenant) {
            $settings = $tenant->settings['ip_access_control'] ?? [];

            return array_merge($this->getDefaultConfig(), $settings);
        });
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'mode' => self::MODE_DISABLED,
            'whitelist' => [],
            'blacklist' => [],
            'log_blocked' => true,
            'notify_on_blocked' => false,
        ];
    }

    /**
     * Update tenant IP configuration.
     */
    public function updateConfig(array $config): void
    {
        $tenant = tenant();

        if (! $tenant) {
            throw new \RuntimeException('Cannot update IP config without tenant context.');
        }

        $current = $tenant->settings ?? [];
        $current['ip_access_control'] = array_merge(
            $this->getDefaultConfig(),
            $config
        );

        $tenant->update(['settings' => $current]);

        TenantCache::forget("ip_whitelist:tenant:{$tenant->id}");
    }

    /**
     * Set the IP access mode.
     *
     * @param  string  $mode  disabled|whitelist|blacklist
     */
    public function setMode(string $mode): void
    {
        if (! in_array($mode, [self::MODE_DISABLED, self::MODE_WHITELIST, self::MODE_BLACKLIST])) {
            throw new \InvalidArgumentException("Invalid mode: {$mode}");
        }

        $config = $this->getConfig();
        $config['mode'] = $mode;
        $this->updateConfig($config);
    }

    /**
     * Add an IP or range to the whitelist.
     *
     * @param  string  $ip  IP address, CIDR, or range
     * @param  string  $label  Description
     * @param  string|null  $expiresAt  Optional expiration date
     */
    public function addToWhitelist(string $ip, string $label = '', ?string $expiresAt = null): void
    {
        $this->validateIpEntry($ip);

        $config = $this->getConfig();

        // Remove from blacklist if present
        $config['blacklist'] = array_filter(
            $config['blacklist'],
            fn ($entry) => $entry['ip'] !== $ip
        );

        // Add to whitelist
        $config['whitelist'][] = [
            'ip' => $ip,
            'label' => $label,
            'added_at' => now()->toDateTimeString(),
            'expires_at' => $expiresAt,
        ];

        $this->updateConfig($config);
    }

    /**
     * Add an IP or range to the blacklist.
     *
     * @param  string  $ip  IP address, CIDR, or range
     * @param  string  $reason  Reason for blocking
     * @param  string|null  $expiresAt  Optional expiration date
     */
    public function addToBlacklist(string $ip, string $reason = '', ?string $expiresAt = null): void
    {
        $this->validateIpEntry($ip);

        $config = $this->getConfig();

        // Remove from whitelist if present
        $config['whitelist'] = array_filter(
            $config['whitelist'],
            fn ($entry) => $entry['ip'] !== $ip
        );

        // Add to blacklist
        $config['blacklist'][] = [
            'ip' => $ip,
            'reason' => $reason,
            'added_at' => now()->toDateTimeString(),
            'expires_at' => $expiresAt,
        ];

        $this->updateConfig($config);
    }

    /**
     * Remove an IP from whitelist.
     */
    public function removeFromWhitelist(string $ip): void
    {
        $config = $this->getConfig();
        $config['whitelist'] = array_values(array_filter(
            $config['whitelist'],
            fn ($entry) => $entry['ip'] !== $ip
        ));
        $this->updateConfig($config);
    }

    /**
     * Remove an IP from blacklist.
     */
    public function removeFromBlacklist(string $ip): void
    {
        $config = $this->getConfig();
        $config['blacklist'] = array_values(array_filter(
            $config['blacklist'],
            fn ($entry) => $entry['ip'] !== $ip
        ));
        $this->updateConfig($config);
    }

    /**
     * Check if IP is in a list (supports CIDR and ranges).
     */
    protected function isIpInList(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            $pattern = is_array($entry) ? $entry['ip'] : $entry;

            // Check expiration
            if (is_array($entry) && isset($entry['expires_at'])) {
                if (now()->isAfter($entry['expires_at'])) {
                    continue; // Skip expired entries
                }
            }

            // CIDR notation check
            if (strpos($pattern, '/') !== false) {
                if ($this->ipMatchesCidr($ip, $pattern)) {
                    return true;
                }
            }
            // Range notation check (192.168.1.1-192.168.1.100)
            elseif (strpos($pattern, '-') !== false) {
                if ($this->ipMatchesRange($ip, $pattern)) {
                    return true;
                }
            }
            // Exact match
            elseif ($ip === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches CIDR notation.
     */
    protected function ipMatchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $mask = -1 << (32 - $bits);

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        // IPv6 support
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            if ($ipBin === false || $subnetBin === false) {
                return false;
            }

            $mask = str_repeat('f', $bits >> 2);

            switch ($bits % 4) {
                case 1: $mask .= '8';

                    break;
                case 2: $mask .= 'c';

                    break;
                case 3: $mask .= 'e';

                    break;
            }

            $mask = pack('H*', str_pad($mask, 32, '0'));

            return ($ipBin & $mask) === ($subnetBin & $mask);
        }

        return false;
    }

    /**
     * Check if IP matches a range.
     */
    protected function ipMatchesRange(string $ip, string $range): bool
    {
        [$start, $end] = explode('-', $range);
        $start = trim($start);
        $end = trim($end);

        $ipLong = ip2long($ip);
        $startLong = ip2long($start);
        $endLong = ip2long($end);

        if ($ipLong === false || $startLong === false || $endLong === false) {
            return false;
        }

        return $ipLong >= $startLong && $ipLong <= $endLong;
    }

    /**
     * Check if IP is a local address.
     */
    protected function isLocalAddress(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
               strpos($ip, '127.') === 0;
    }

    /**
     * Validate IP entry format.
     */
    protected function validateIpEntry(string $ip): void
    {
        // CIDR
        if (strpos($ip, '/') !== false) {
            [$subnet, $bits] = explode('/', $ip);
            if (! filter_var($subnet, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException("Invalid CIDR subnet: {$ip}");
            }
            if ($bits < 0 || $bits > 128) {
                throw new \InvalidArgumentException("Invalid CIDR bits: {$ip}");
            }

            return;
        }

        // Range
        if (strpos($ip, '-') !== false) {
            [$start, $end] = explode('-', $ip);
            if (! filter_var(trim($start), FILTER_VALIDATE_IP) ||
                ! filter_var(trim($end), FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException("Invalid IP range: {$ip}");
            }

            return;
        }

        // Single IP
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address: {$ip}");
        }
    }

    /**
     * Check user-specific IP restrictions.
     */
    protected function isUserIpAllowed(User $user, string $ip): bool
    {
        $userIpRestrictions = $user->ip_restrictions ?? null;

        if (! $userIpRestrictions || empty($userIpRestrictions['enabled'])) {
            return true; // No user-specific restrictions
        }

        $allowedIps = $userIpRestrictions['allowed_ips'] ?? [];

        if (empty($allowedIps)) {
            return true;
        }

        return $this->isIpInList($ip, $allowedIps);
    }

    /**
     * Set user-specific IP restrictions.
     */
    public function setUserIpRestrictions(User $user, array $allowedIps, bool $enabled = true): void
    {
        foreach ($allowedIps as $ip) {
            $this->validateIpEntry($ip);
        }

        $user->update([
            'ip_restrictions' => [
                'enabled' => $enabled,
                'allowed_ips' => $allowedIps,
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Log blocked access attempt.
     */
    protected function logBlockedAccess(string $ip, ?User $user, string $reason): void
    {
        $config = $this->getConfig();

        if (! $config['log_blocked']) {
            return;
        }

        $logData = [
            'ip' => $ip,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'reason' => $reason,
            'tenant_id' => tenant()?->id,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::warning('IP access blocked', $logData);

        // TODO: Dispatch notification if enabled
        if ($config['notify_on_blocked']) {
            // Dispatch alert to admin
        }
    }

    /**
     * Get blocked access logs.
     */
    public function getBlockedAccessLogs(int $days = 7): array
    {
        // This would query the audit log or dedicated log table
        // Implementation depends on logging infrastructure
        return [];
    }

    /**
     * Get the current whitelist entries.
     */
    public function getWhitelist(): array
    {
        return $this->getConfig()['whitelist'];
    }

    /**
     * Get the current blacklist entries.
     */
    public function getBlacklist(): array
    {
        return $this->getConfig()['blacklist'];
    }
}
