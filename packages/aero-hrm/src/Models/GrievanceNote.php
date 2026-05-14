<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grievance Note Model
 *
 * Notes and updates added to grievance cases during investigation.
 */
class GrievanceNote extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'grievance_id',
        'user_id',
        'note_type',
        'content',
        'is_internal',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'attachments' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Note types.
     */
    public const TYPE_UPDATE = 'update';

    public const TYPE_INVESTIGATION = 'investigation';

    public const TYPE_RESOLUTION = 'resolution';

    public const TYPE_ESCALATION = 'escalation';

    public const TYPE_COMMUNICATION = 'communication';

    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope for internal notes.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope for employee-visible notes.
     */
    public function scopeEmployeeVisible($query)
    {
        return $query->where('is_internal', false);
    }
}
