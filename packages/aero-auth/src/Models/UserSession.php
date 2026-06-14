<?php

declare(strict_types=1);

namespace Aero\Auth\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Session Model
 *
 * Tracks individual user login sessions for security and device management.
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_token
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $device_type
 * @property string $browser
 * @property string $platform
 * @property string|null $location
 * @property bool $is_current
 * @property Carbon $last_active_at
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserSession extends Model
{
    /**
     * Default audit label (mirrors the value previously inherited from TenantModel;
     * auth is now context-free, so identity models are plain Models — same pattern
     * as {@see User}/{@see UserDevice}).
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    protected $fillable = [
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'is_current',
        'last_active_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'last_active_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active (non-expired) sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired sessions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Get formatted device info for display.
     */
    public function getDeviceInfoAttribute(): string
    {
        return "{$this->browser} on {$this->platform}";
    }
}
