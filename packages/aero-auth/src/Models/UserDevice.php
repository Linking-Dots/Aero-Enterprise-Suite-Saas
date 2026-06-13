<?php

namespace Aero\Auth\Models;

use Aero\Contracts\Models\Concerns\EnforcesTenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auth-identity unification (Unit 2D-step3): UserDevice is dual-context — central
 * landlord devices AND tenant-user devices, all in one `user_devices` table per
 * connection. It therefore follows the SAME pattern as {@see User}: a plain Model
 * with the {@see EnforcesTenantContext} guard (which no-ops for instances on the
 * 'central' connection) rather than {@see \Aero\Contracts\Models\TenantModel}
 * (whose guard has no central escape and would throw for landlord/console queries).
 * Eloquent relationship-connection inheritance routes $landlordUser->devices() onto
 * the central connection, so the escape fires; tenant users keep the fail-closed guard.
 * Replaces the former platform CentralModel duplicate.
 */
class UserDevice extends Model
{
    use EnforcesTenantContext;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_token',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'ip_address',
        'user_agent',
        'is_active',
        'is_trusted',
        'last_used_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_trusted' => 'boolean',
            'last_used_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Default audit label — mirrors the value previously inherited from
     * TenantModel. AuditService::log() calls $subject->getAuditLabel(); since
     * this model no longer extends TenantModel it must supply it directly
     * (same pattern as {@see User}).
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active devices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get trusted devices.
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Mark device as active and update last used timestamp.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_active' => true,
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Deactivate this device.
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Mark device as trusted (e.g., after OTP verification).
     */
    public function markAsTrusted(): bool
    {
        return $this->update([
            'is_trusted' => true,
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Get formatted device info for display.
     */
    public function getFormattedInfoAttribute(): string
    {
        $parts = array_filter([
            $this->browser,
            $this->platform,
            $this->device_type,
        ]);

        return implode(' • ', $parts) ?: 'Unknown Device';
    }
}
