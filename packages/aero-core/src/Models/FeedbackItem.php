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
 * Tenant-scoped product-feedback model. Replaces raw DB::table('feedback_items')
 * calls in HelpSupportController.
 */
class FeedbackItem extends TenantModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'status',
        'votes',
        'response',
    ];

    protected $casts = [
        'votes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeMostVoted($query)
    {
        return $query->orderByDesc('votes')->orderByDesc('created_at');
    }
}
