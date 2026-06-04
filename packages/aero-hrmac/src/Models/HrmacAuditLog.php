<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;

class HrmacAuditLog extends HrmacModel
{
    protected $table = 'hrmac_audit_log';

    public const EVENT_ROLE_MUTATION  = 'role_mutation';
    public const EVENT_ACCESS_DENIED  = 'access_denied';
    public const EVENT_ACCESS_GRANTED = 'access_granted';

    protected $fillable = [
        'event',
        'actor_user_id',
        'role_id',
        'action',
        'module_code',
        'sub_module_code',
        'component_code',
        'action_code',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
        'path',
        'method',
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

    public function scopeDenials($query)
    {
        return $query->where('event', self::EVENT_ACCESS_DENIED);
    }

    public function scopeRoleMutations($query)
    {
        return $query->where('event', self::EVENT_ROLE_MUTATION);
    }
}
