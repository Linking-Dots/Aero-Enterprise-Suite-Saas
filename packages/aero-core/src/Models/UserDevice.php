<?php

namespace Aero\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends TenantModel
{
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
