<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class QuotaEnforcementSetting extends CentralModel
{
    protected $fillable = [
        'quota_type',
        'warning_threshold_percentage',
        'critical_threshold_percentage',
        'block_threshold_percentage',
        'warning_period_days',
        'send_email',
        'send_sms',
        'block_on_exceed',
        'escalation_frequency',
        'notification_preferences',
    ];

    protected $casts = [
        'warning_threshold_percentage' => 'integer',
        'critical_threshold_percentage' => 'integer',
        'block_threshold_percentage' => 'integer',
        'warning_period_days' => 'integer',
        'send_email' => 'boolean',
        'send_sms' => 'boolean',
        'block_on_exceed' => 'boolean',
        'notification_preferences' => 'array',
    ];

    /**
     * Get settings for a specific quota type.
     */
    public static function forQuotaType(string $quotaType): ?self
    {
        return static::where('quota_type', $quotaType)->first();
    }

    /**
     * Get the escalation frequency as hours.
     */
    public function getEscalationHoursAttribute(): int
    {
        return match ($this->escalation_frequency) {
            'realtime' => 1,
            'hourly' => 1,
            'daily' => 24,
            default => 24,
        };
    }
}
