<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmEvent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hrm_events';

    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'status',
        'is_public',
        'capacity',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(HrmEventSession::class, 'event_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(HrmEventRegistration::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
