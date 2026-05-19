<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmDisciplinaryCaseEvent extends TenantModel
{
    protected $table = 'hrm_disciplinary_case_events';

    public $timestamps = false;

    protected $fillable = ['case_id', 'type', 'payload', 'actor_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(HrmDisciplinaryCase::class, 'case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
