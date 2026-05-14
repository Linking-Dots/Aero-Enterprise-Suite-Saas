<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'venue',
        'event_date',
        'event_time',
        'banner_image',
        'description',
        'food_details',
        'rules',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'registration_deadline',
        'max_participants',
        'is_published',
        'is_registration_open',
        'venue_map_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'registration_deadline' => 'datetime',
        'is_published' => 'boolean',
        'is_registration_open' => 'boolean',
    ];

    protected $appends = ['full_event_datetime', 'registration_status', 'participants_count'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    // Relationships
    public function subEvents(): HasMany
    {
        return $this->hasMany(SubEvent::class)->orderBy('display_order');
    }

    public function activeSubEvents(): HasMany
    {
        return $this->subEvents()->where('is_active', true);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function approvedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'approved');
    }

    public function pendingRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'pending');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(EventCustomField::class)->orderBy('display_order');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(EventActivityLog::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getFullEventDatetimeAttribute(): string
    {
        return $this->event_date->format('F j, Y').' at '.$this->event_time->format('g:i A');
    }

    public function getRegistrationStatusAttribute(): string
    {
        if (! $this->is_registration_open) {
            return 'closed';
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return 'expired';
        }

        if ($this->max_participants && $this->approvedRegistrations()->count() >= $this->max_participants) {
            return 'full';
        }

        return 'open';
    }

    public function getParticipantsCountAttribute(): int
    {
        return $this->approvedRegistrations()->count();
    }

    // Helper methods
    public function canRegister(): bool
    {
        return $this->registration_status === 'open';
    }

    public function hasAvailableSlots(): bool
    {
        if (! $this->max_participants) {
            return true;
        }

        return $this->participants_count < $this->max_participants;
    }

    public function getRemainingSlots(): ?int
    {
        if (! $this->max_participants) {
            return null;
        }

        return max(0, $this->max_participants - $this->participants_count);
    }

    public function getAnalytics(): array
    {
        $registrations = $this->registrations;

        return [
            'total_registrations' => $registrations->count(),
            'approved' => $registrations->where('status', 'approved')->count(),
            'pending' => $registrations->where('status', 'pending')->count(),
            'rejected' => $registrations->where('status', 'rejected')->count(),
            'payment_verified' => $registrations->where('payment_verified', true)->count(),
            'payment_pending' => $registrations->where('payment_verified', false)->count(),
            'gender_distribution' => [
                'male' => $registrations->where('gender', 'male')->count(),
                'female' => $registrations->where('gender', 'female')->count(),
                'other' => $registrations->where('gender', 'other')->count(),
            ],
        ];
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now()->toDateString());
    }
}
