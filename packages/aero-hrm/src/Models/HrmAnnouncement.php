<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmAnnouncement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hrm_announcements';

    protected $fillable = [
        'title',
        'body',
        'target_department_ids',
        'target_role_ids',
        'is_global',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'target_department_ids' => 'array',
            'target_role_ids' => 'array',
            'is_global' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(HrmAnnouncementRead::class, 'announcement_id');
    }
}
