<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubEvent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'schedule',
        'prize_info',
        'max_participants',
        'joining_fee',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'joining_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['participants_count', 'is_full'];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(EventRegistration::class, 'event_registration_sub_events')
            ->withTimestamps();
    }

    public function approvedRegistrations(): BelongsToMany
    {
        return $this->registrations()->where('event_registrations.status', 'approved');
    }

    // Accessors
    public function getParticipantsCountAttribute(): int
    {
        return $this->approvedRegistrations()->count();
    }

    public function getIsFullAttribute(): bool
    {
        if (! $this->max_participants) {
            return false;
        }

        return $this->participants_count >= $this->max_participants;
    }

    // Helper methods
    public function hasAvailableSlots(): bool
    {
        return ! $this->is_full;
    }

    public function getRemainingSlots(): ?int
    {
        if (! $this->max_participants) {
            return null;
        }

        return max(0, $this->max_participants - $this->participants_count);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
