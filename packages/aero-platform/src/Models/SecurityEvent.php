<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends CentralModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'user_agent',
        'location',
        'metadata',
        'risk_score',
        'investigated',
        'investigation_notes',
        'investigated_at',
        'investigated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'investigated' => 'boolean',
        'investigated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that triggered this security event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who investigated this event
     */
    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    /**
     * Scope for high-risk events
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', 'high');
    }

    /**
     * Scope for recent events
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific event types
     */
    public function scopeEventType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for specific IP address
     */
    public function scopeFromIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }
}
