<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prospect Lead Model
 *
 * Represents potential customers who have shown interest in the platform
 * but haven't yet registered as tenants.
 */
class ProspectLead extends CentralModel
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'central';

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_LOST = 'lost';

    public const SOURCE_WEBSITE = 'website';

    public const SOURCE_REFERRAL = 'referral';

    public const SOURCE_SOCIAL = 'social';

    public const SOURCE_ADVERTISING = 'advertising';

    public const SOURCE_EVENT = 'event';

    public const SOURCE_NEWSLETTER = 'newsletter';

    public const SOURCE_DEMO_REQUEST = 'demo_request';

    public const INTEREST_LOW = 'low';

    public const INTEREST_MEDIUM = 'medium';

    public const INTEREST_HIGH = 'high';

    protected $fillable = [
        'email',
        'name',
        'company_name',
        'phone',
        'country',
        'source',
        'source_detail',
        'status',
        'score',
        'interest_level',
        'interests',
        'utm_data',
        'metadata',
        'notes',
        'assigned_to',
        'converted_tenant_id',
        'contacted_at',
        'qualified_at',
        'converted_at',
        'last_activity_at',
    ];

    protected $casts = [
        'interests' => 'array',
        'utm_data' => 'array',
        'metadata' => 'array',
        'score' => 'integer',
        'contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
        'source' => self::SOURCE_WEBSITE,
        'score' => 0,
        'interests' => '[]',
        'utm_data' => '[]',
        'metadata' => '[]',
    ];

    /**
     * Get the landlord user assigned to this lead.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the tenant this lead was converted to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'converted_tenant_id');
    }

    /**
     * Scope for new leads.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope for qualified leads.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope for high-value leads.
     */
    public function scopeHighValue($query, int $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    /**
     * Scope by source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for unassigned leads.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Mark as contacted.
     */
    public function markAsContacted(): bool
    {
        return $this->update([
            'status' => self::STATUS_CONTACTED,
            'contacted_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Mark as qualified.
     */
    public function markAsQualified(): bool
    {
        return $this->update([
            'status' => self::STATUS_QUALIFIED,
            'qualified_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Mark as converted to tenant.
     */
    public function markAsConverted(int $tenantId): bool
    {
        return $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_tenant_id' => $tenantId,
            'converted_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Mark as lost.
     */
    public function markAsLost(?string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['lost_reason'] = $reason;

        return $this->update([
            'status' => self::STATUS_LOST,
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Calculate and update lead score based on various factors.
     */
    public function calculateScore(): int
    {
        $score = 0;

        // Base score for having email
        $score += 10;

        // Additional fields
        if ($this->name) {
            $score += 5;
        }
        if ($this->company_name) {
            $score += 10;
        }
        if ($this->phone) {
            $score += 10;
        }

        // Source scoring
        $sourceScores = [
            self::SOURCE_DEMO_REQUEST => 25,
            self::SOURCE_REFERRAL => 20,
            self::SOURCE_EVENT => 15,
            self::SOURCE_WEBSITE => 10,
            self::SOURCE_SOCIAL => 5,
            self::SOURCE_ADVERTISING => 5,
        ];
        $score += $sourceScores[$this->source] ?? 5;

        // Interest level
        if ($this->interest_level === self::INTEREST_HIGH) {
            $score += 20;
        } elseif ($this->interest_level === self::INTEREST_MEDIUM) {
            $score += 10;
        }

        // Engagement bonus
        if ($this->contacted_at) {
            $score += 5;
        }

        // Cap at 100
        $score = min(100, $score);

        $this->update(['score' => $score]);

        return $score;
    }

    /**
     * Get all status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_LOST => 'Lost',
        ];
    }

    /**
     * Get all source options.
     */
    public static function getSourceOptions(): array
    {
        return [
            self::SOURCE_WEBSITE => 'Website',
            self::SOURCE_REFERRAL => 'Referral',
            self::SOURCE_SOCIAL => 'Social Media',
            self::SOURCE_ADVERTISING => 'Advertising',
            self::SOURCE_EVENT => 'Event',
            self::SOURCE_NEWSLETTER => 'Newsletter',
            self::SOURCE_DEMO_REQUEST => 'Demo Request',
        ];
    }
}
