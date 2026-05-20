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

    /**
     * All columns from the original (2025_12_24) and P-3 (2026_05_21_030006)
     * consolidation migration. Legacy notification columns coexist with new
     * resource/limit columns to allow a zero-downtime column addition strategy.
     */
    protected $fillable = [
        // P-3 resource-based columns
        'resource',
        'default_limit',
        'warning_threshold_pct',
        'hard_limit_pct',
        'action',
        // Original schema columns
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
            // P-3 resource-based casts
            'default_limit' => 'integer',
            'warning_threshold_pct' => 'integer',
            'hard_limit_pct' => 'integer',
            // Original schema casts
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
