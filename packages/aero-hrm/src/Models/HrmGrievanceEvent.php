<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmGrievanceEvent extends TenantModel
{
    protected $table = 'hrm_grievance_events';

    public $timestamps = false;

    protected $fillable = ['grievance_id', 'type', 'payload', 'actor_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }

    public function grievance(): BelongsTo
    {
        return $this->belongsTo(HrmGrievance::class, 'grievance_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
