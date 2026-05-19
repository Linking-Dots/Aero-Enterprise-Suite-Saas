<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmAnnouncementRead extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_announcement_reads';

    public $timestamps = false;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(HrmAnnouncement::class, 'announcement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
