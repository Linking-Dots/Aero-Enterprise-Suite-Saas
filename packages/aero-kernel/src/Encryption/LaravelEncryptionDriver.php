<?php

declare(strict_types=1);

namespace Aero\Kernel\Encryption;

use Aero\Contracts\EncryptionDriverInterface;
use Illuminate\Contracts\Encryption\Encrypter;

/**
 * Default encryption driver using Laravel's AES-256-CBC encrypter.
 *
 * The $keyId parameter is ignored in this driver — all encryption uses
 * the application key. When a tenant upgrades to Zero-Trust tier,
 * swap this for AwsKmsEncryptionDriver with no model changes needed.
 */
final class LaravelEncryptionDriver implements EncryptionDriverInterface
{
    public function __construct(private readonly Encrypter $encrypter) {}

    public function encrypt(string $value, ?string $keyId = null): string
    {
        return $this->encrypter->encryptString($value);
    }

    public function decrypt(string $ciphertext, ?string $keyId = null): string
    {
        return $this->encrypter->decryptString($ciphertext);
    }
}
