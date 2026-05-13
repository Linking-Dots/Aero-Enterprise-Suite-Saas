<?php

namespace Aero\Platform\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integrations_api_keys';

    protected $fillable = [
        'user_id', 'name', 'key', 'scopes', 'is_active', 'last_used_at', 'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['key'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
