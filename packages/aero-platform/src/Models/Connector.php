<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Connector extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integrations_connectors';

    protected $fillable = [
        'name', 'type', 'description', 'config', 'is_active', 'last_sync_at', 'status',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }
}
