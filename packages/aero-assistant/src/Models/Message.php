<?php

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends TenantModel
{
    use HasFactory;

    protected $table = 'assistant_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'has_error',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'has_error' => 'boolean',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Scope to get user messages.
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope to get assistant messages.
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope to get messages without errors.
     */
    public function scopeWithoutErrors($query)
    {
        return $query->where('has_error', false);
    }

    /**
     * Check if this is a user message.
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }
}
