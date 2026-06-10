<?php

declare(strict_types=1);

namespace Aero\License;

/**
 * Single source of truth for the Aero license-key format + checksum algorithm.
 *
 * Dependency decoupling (V7): the checksum derivation was previously duplicated,
 * byte-for-byte, in aero-core's LicenseValidator (verification) and aero-platform's
 * LicenseIssuer (generation), kept aligned only by a hand-written comment. Both now
 * delegate here so the algorithm can never drift.
 *
 * Key format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX (four 8-char A-Z0-9 segments). The
 * first two characters of the fourth segment are a checksum of the first three
 * segments concatenated with a configurable salt.
 */
final class LicenseSignature
{
    /** Structural pattern every license key must match. */
    public const FORMAT_PATTERN = '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/';

    /** Resolve the checksum salt (overridable via config). */
    public static function salt(): string
    {
        return config('license.checksum_salt', 'aero-license-salt');
    }

    /**
     * The 2-character checksum for the three leading data segments.
     *
     * @param  string  $segment1  First 8-char segment.
     * @param  string  $segment2  Second 8-char segment.
     * @param  string  $segment3  Third 8-char segment.
     */
    public static function checksum(string $segment1, string $segment2, string $segment3): string
    {
        return strtoupper(substr(md5($segment1.$segment2.$segment3.self::salt()), 0, 2));
    }

    /** True when the key matches the structural format (case-insensitive). */
    public static function matchesFormat(string $licenseKey): bool
    {
        return (bool) preg_match(self::FORMAT_PATTERN, strtoupper(trim($licenseKey)));
    }

    /**
     * Verify the embedded checksum of a full license key.
     *
     * Mirrors the historical LicenseValidator::verifyChecksum() behaviour exactly:
     * splits on '-', requires four segments, and constant-time compares the recomputed
     * checksum against the first two characters of the final segment.
     */
    public static function verify(string $licenseKey): bool
    {
        $segments = explode('-', strtoupper(trim($licenseKey)));

        if (count($segments) !== 4) {
            return false;
        }

        $expected = self::checksum($segments[0], $segments[1], $segments[2]);

        return hash_equals($expected, substr($segments[3], 0, 2));
    }
}
