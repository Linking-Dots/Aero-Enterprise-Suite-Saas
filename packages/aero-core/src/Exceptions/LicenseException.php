<?php

// packages/aero-core/src/Exceptions/LicenseException.php

namespace Aero\Core\Exceptions;

class LicenseException extends \RuntimeException
{
    public static function invalidFormat(): self
    {
        return new self('License key format is invalid. Expected format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX');
    }

    public static function activationFailed(string $reason): self
    {
        return new self("License activation failed: {$reason}");
    }

    public static function domainMismatch(): self
    {
        return new self('License is bound to a different domain. Contact support to transfer your license.');
    }

    public static function expired(): self
    {
        return new self('Your license has expired. Please renew at aerosuite.com/renew');
    }

    public static function graceExpired(): self
    {
        return new self('License validation grace period has expired. Please ensure internet connectivity and restart.');
    }
}
