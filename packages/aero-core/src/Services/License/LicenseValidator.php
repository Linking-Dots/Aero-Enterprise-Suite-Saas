<?php

// packages/aero-core/src/Services/License/LicenseValidator.php

namespace Aero\Core\Services\License;

use Aero\Core\Exceptions\LicenseException;
use Aero\License\LicenseSignature;

class LicenseValidator
{
    public function validateFormat(string $licenseKey): void
    {
        if (! LicenseSignature::matchesFormat($licenseKey)) {
            throw LicenseException::invalidFormat();
        }
    }

    public function verifyChecksum(string $licenseKey): bool
    {
        // Delegates to the single-source signing core (aero-license) so verification
        // can never drift from issuance.
        return LicenseSignature::verify($licenseKey);
    }
}
