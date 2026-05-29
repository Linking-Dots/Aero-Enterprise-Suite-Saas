<?php

declare(strict_types=1);

namespace Aero\Core\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan 02 (aero-core) Task 5 of foundation 10/10 push.
 *
 * Tenant-scoped support ticket model. Replaces the raw DB::table('support_tickets')
 * calls in HelpSupportController, which previously crashed in production because
 * the table didn't exist.
 */
class SupportTicket extends TenantModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'subject',
        'body',
        'status',
        'priority',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
