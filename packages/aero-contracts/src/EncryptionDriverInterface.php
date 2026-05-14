<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Swappable encryption driver for PII fields.
 *
 * Default: LaravelEncryptionDriver (AES-256-CBC via app('encrypter')).
 * Future: AwsKmsEncryptionDriver, VaultEncryptionDriver.
 *
 * All encrypted model fields use the EncryptedField cast which
 * delegates to this interface — swapping the driver upgrades every
 * encrypted column without touching a single model.
 */
interface EncryptionDriverInterface
{
    /**
     * Encrypt a plain-text value for storage.
     *
     * @param  string  $value       Plain-text value to encrypt
     * @param  string|null  $keyId  Tenant-specific key ID (null = platform master key)
     */
    public function encrypt(string $value, ?string $keyId = null): string;

    /**
     * Decrypt a stored cipher-text value.
     *
     * @param  string  $ciphertext  Encrypted value from storage
     * @param  string|null  $keyId  Tenant-specific key ID (null = platform master key)
     */
    public function decrypt(string $ciphertext, ?string $keyId = null): string;
}
