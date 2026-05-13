<?php

namespace Aero\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataExport extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'entity_type',
        'format',
        'status',
        'file_path',
        'record_count',
        'filters',
        'scheduled_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The tenant that owns the export.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    /**
     * The user that created the export.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if export is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if export is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if export is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
}
