<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuotaEnforcementSetting extends CentralModel
{
    use HasFactory;

    public const ACTION_WARN = 'warn';

    public const ACTION_THROTTLE = 'throttle';

    public const ACTION_BLOCK = 'block';

    protected $connection = 'central';

    protected $table = 'quota_enforcement_settings';

    protected $fillable = [
        'resource',
        'default_limit',
        'warning_threshold_pct',
        'hard_limit_pct',
        'action',
        // Legacy columns (original schema)
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

    protected function casts(): array
    {
        return [
            'default_limit' => 'integer',
            'warning_threshold_pct' => 'integer',
            'hard_limit_pct' => 'integer',
            // Legacy casts
            'warning_threshold_percentage' => 'integer',
            'critical_threshold_percentage' => 'integer',
            'block_threshold_percentage' => 'integer',
            'warning_period_days' => 'integer',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'block_on_exceed' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get settings for a specific quota type (legacy).
     */
    public static function forQuotaType(string $quotaType): ?self
    {
        return static::where('quota_type', $quotaType)->first();
    }

    /**
     * Get the escalation frequency as hours (legacy).
     */
    public function getEscalationHoursAttribute(): int
    {
        return match ($this->escalation_frequency) {
            'realtime', 'hourly' => 1,
            default => 24,
        };
    }
}
