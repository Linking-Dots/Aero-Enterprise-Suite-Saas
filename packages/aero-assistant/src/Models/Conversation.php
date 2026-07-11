<?php

declare(strict_types=1);

namespace Aero\Assistant\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends AeonModel
{
    protected $table = 'aeon_conversations';

    protected $fillable = ['user_id', 'title', 'context', 'archived_at'];

    protected $casts = ['context' => 'array', 'archived_at' => 'datetime'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
