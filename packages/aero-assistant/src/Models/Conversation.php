<?php

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends TenantModel
{
    use HasFactory;

    protected $table = 'assistant_conversations';

    protected $fillable = [
        'user_id',
        'title',
        'context',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'context' => 'array',
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \Aero\Core\Models\User::class));
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }

    /**
     * Scope to get active (non-archived) conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get archived conversations.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Generate a title from the first user message if not set.
     */
    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstMessage = $this->messages()
            ->where('role', 'user')
            ->oldest()
            ->first();

        if ($firstMessage) {
            $this->title = \Illuminate\Support\Str::limit($firstMessage->content, 50, '...');
            $this->save();
        }
    }
}
