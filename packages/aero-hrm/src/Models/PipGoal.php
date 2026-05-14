<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipGoal extends TenantModel
{
    protected $table = 'pip_goals';

    protected $fillable = [
        'pip_plan_id',
        'title',
        'description',
        'target_date',
        'status',
        'progress_notes',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => 'string',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function pipPlan(): BelongsTo
    {
        return $this->belongsTo(PipPlan::class, 'pip_plan_id');
    }
}
