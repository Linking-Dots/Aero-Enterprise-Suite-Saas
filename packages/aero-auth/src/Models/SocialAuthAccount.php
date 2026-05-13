<?php

declare(strict_types=1);

namespace Aero\Auth\Models;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Social Auth Account Model
 *
 * Stores OAuth provider links for social login.
 */
class SocialAuthAccount extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_LINKEDIN = 'linkedin';

    public const PROVIDER_FACEBOOK = 'facebook';

    public const PROVIDER_GITHUB = 'github';

    protected $fillable = [
        'provider',
        'provider_user_id',
        'email',
        'name',
        'avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_data',
        'authenticatable_type',
        'authenticatable_id',
        'pending_registration_token',
        'pending_expires_at',
    ];

    protected $casts = [
        'provider_data' => 'array',
        'token_expires_at' => 'datetime',
        'pending_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'pending_registration_token',
    ];

    /**
     * Get the authenticatable model (landlord user or tenant user).
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope by provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if the token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if this is a pending registration.
     */
    public function isPendingRegistration(): bool
    {
        return ! empty($this->pending_registration_token)
            && ($this->pending_expires_at === null || $this->pending_expires_at->isFuture());
    }

    /**
     * Link to an authenticatable model.
     */
    public function linkTo(\Illuminate\Database\Eloquent\Model $authenticatable): bool
    {
        return $this->update([
            'authenticatable_type' => get_class($authenticatable),
            'authenticatable_id' => $authenticatable->getKey(),
            'pending_registration_token' => null,
            'pending_expires_at' => null,
        ]);
    }

    /**
     * Find by provider and provider user ID.
     */
    public static function findByProvider(string $provider, string $providerUserId): ?self
    {
        return static::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * Find by pending registration token.
     */
    public static function findByPendingToken(string $token): ?self
    {
        return static::where('pending_registration_token', $token)
            ->where(function ($query) {
                $query->whereNull('pending_expires_at')
                    ->orWhere('pending_expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Create or update from OAuth data.
     */
    public static function updateOrCreateFromOAuth(
        string $provider,
        string $providerUserId,
        array $data
    ): self {
        return static::updateOrCreate(
            [
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ],
            $data
        );
    }

    /**
     * Get supported providers.
     */
    public static function getSupportedProviders(): array
    {
        return [
            self::PROVIDER_GOOGLE => [
                'name' => 'Google',
                'icon' => 'google',
                'color' => '#4285F4',
            ],
            self::PROVIDER_LINKEDIN => [
                'name' => 'LinkedIn',
                'icon' => 'linkedin',
                'color' => '#0A66C2',
            ],
            self::PROVIDER_FACEBOOK => [
                'name' => 'Facebook',
                'icon' => 'facebook',
                'color' => '#1877F2',
            ],
            self::PROVIDER_GITHUB => [
                'name' => 'GitHub',
                'icon' => 'github',
                'color' => '#24292F',
            ],
        ];
    }
}
