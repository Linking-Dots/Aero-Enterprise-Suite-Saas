<?php

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends TenantModel
{
    use HasFactory;

    protected $table = 'assistant_usage_logs';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'action_type',
        'query',
        'response',
        'tokens_used',
        'processing_time_ms',
        'used_rag',
        'rag_chunks_retrieved',
    ];

    protected $casts = [
        'used_rag' => 'boolean',
        'tokens_used' => 'integer',
        'processing_time_ms' => 'integer',
        'rag_chunks_retrieved' => 'integer',
    ];

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \Aero\Core\Models\User::class));
    }

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
