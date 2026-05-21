<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * P-7 — Platform API Key.
 *
 * The raw key is returned exactly once at creation; thereafter only key_hash
 * (bcrypt) is stored. key_prefix (first 8 chars) is stored in plaintext for
 * display/lookup.
 */
class PlatformApiKey extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integrations_api_keys';

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'key',
        'scopes',
        'is_active',
        'last_used_at',
        'expires_at',
        'revoked_at',
        'created_by',
        'user_id',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_by' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = ['key', 'key_hash'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getAuditLabel(): string
    {
        return "API Key: {$this->name} ({$this->key_prefix}...)";
    }
}
