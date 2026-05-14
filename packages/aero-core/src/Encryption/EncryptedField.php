<?php

declare(strict_types=1);

namespace Aero\Core\Encryption;

use Aero\Contracts\EncryptionDriverInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent cast for PII fields.
 *
 * Usage in any model:
 *   protected $casts = [
 *       'account_number' => EncryptedField::class,
 *       'tax_id'         => EncryptedField::class,
 *   ];
 *
 * Delegates to EncryptionDriverInterface — swap the driver binding in
 * AeroCoreServiceProvider to upgrade from Laravel Crypt to AWS KMS
 * without touching this class or any model.
 */
final class EncryptedField implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return app(EncryptionDriverInterface::class)->decrypt(
                $value,
                $model->encryption_key_id ?? null,
            );
        } catch (\Throwable) {
            // Return null rather than exposing a decryption error to the UI
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(EncryptionDriverInterface::class)->encrypt(
            (string) $value,
            $model->encryption_key_id ?? null,
        );
    }
}
