<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Illuminate\Database\Eloquent\Model;

class HrmacAuditLog extends Model
{
    protected $table = 'hrmac_audit_log';

    protected $fillable = [
        'actor_user_id',
        'role_id',
        'action',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
    ];

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
