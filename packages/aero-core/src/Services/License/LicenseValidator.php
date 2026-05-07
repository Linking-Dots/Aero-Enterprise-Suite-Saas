<?php

// packages/aero-core/src/Services/License/LicenseValidator.php

namespace Aero\Core\Services\License;

use Aero\Core\Exceptions\LicenseException;

class LicenseValidator
{
    private const FORMAT_PATTERN = '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/';

    public function validateFormat(string $licenseKey): void
    {
        $key = strtoupper(trim($licenseKey));
        if (! preg_match(self::FORMAT_PATTERN, $key)) {
            throw LicenseException::invalidFormat();
        }
    }

    public function verifyChecksum(string $licenseKey): bool
    {
        $segments = explode('-', strtoupper(trim($licenseKey)));
        if (count($segments) !== 4) {
            return false;
        }

        $dataSegments = implode('', array_slice($segments, 0, 3));
        $salt = config('license.checksum_salt', 'aero-license-salt');
        $expectedChecksum = strtoupper(substr(md5($dataSegments.$salt), 0, 2));
        $providedChecksum = substr($segments[3], 0, 2);

        return hash_equals($expectedChecksum, $providedChecksum);
    }
}
