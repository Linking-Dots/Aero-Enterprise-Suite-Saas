<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * TenantImpersonationToken Model
 *
 * Stores secure tokens for platform admin impersonation of tenant users.
 * Tokens are short-lived (5 minutes) and single-use for security.
 *
 * @property string $token The secure random token (primary key)
 * @property string $tenant_id The tenant being impersonated
 * @property string $user_id The tenant user to log in as
 * @property string $auth_guard The authentication guard to use
 * @property string $redirect_url Where to redirect after login
 * @property Carbon $created_at Token creation timestamp
 */
class TenantImpersonationToken extends CentralModel
{
    /**
     * Token expiration time in minutes.
     */
    public const EXPIRATION_MINUTES = 5;

    /**
     * The table associated with the model.
     */
    protected $table = 'tenant_user_impersonation_tokens';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'token';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used in this table.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'token',
        'tenant_id',
        'user_id',
        'auth_guard',
        'redirect_url',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user that this token is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Generate a new secure impersonation token.
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(64).microtime(true));
    }

    /**
     * Create a new impersonation token for a tenant user.
     */
    public static function createForUser(
        string $tenantId,
        string $userId,
        string $redirectUrl = '/dashboard',
        string $authGuard = 'web'
    ): self {
        // Delete any existing tokens for this user (single-use enforcement)
        static::where('user_id', $userId)->delete();

        return static::create([
            'token' => static::generateToken(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'auth_guard' => $authGuard,
            'redirect_url' => $redirectUrl,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->created_at->addMinutes(self::EXPIRATION_MINUTES)->isPast();
    }

    /**
     * Find a valid (non-expired) token.
     */
    public static function findValid(string $token): ?self
    {
        $impersonationToken = static::find($token);

        if (! $impersonationToken) {
            return null;
        }

        if ($impersonationToken->isExpired()) {
            $impersonationToken->delete();

            return null;
        }

        return $impersonationToken;
    }

    /**
     * Consume the token (delete after use).
     */
    public function consume(): void
    {
        $this->delete();
    }

    /**
     * Delete all expired tokens (cleanup job).
     */
    public static function deleteExpired(): int
    {
        return static::where('created_at', '<', now()->subMinutes(self::EXPIRATION_MINUTES))
            ->delete();
    }
}
