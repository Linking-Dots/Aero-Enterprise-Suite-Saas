<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface LicenseServiceInterface
{
    /** @throws \Aero\Core\Exceptions\LicenseException if format is invalid */
    public function validateFormat(string $licenseKey): void;

    /** @throws \Aero\Core\Exceptions\LicenseException if activation fails */
    public function activate(string $licenseKey, string $productId): void;

    public function isValid(): bool;

    public function status(): string;

    public function graceSecondsRemaining(): int;
}
