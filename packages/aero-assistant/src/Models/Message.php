<?php

declare(strict_types=1);

namespace Aero\Assistant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends AeonModel
{
    protected $table = 'aeon_messages';

    protected $fillable = ['conversation_id', 'role', 'content', 'blocks', 'tool_calls', 'tokens', 'provider', 'model', 'feedback'];

    protected $casts = ['blocks' => 'array', 'tool_calls' => 'array', 'tokens' => 'integer', 'feedback' => 'integer'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
