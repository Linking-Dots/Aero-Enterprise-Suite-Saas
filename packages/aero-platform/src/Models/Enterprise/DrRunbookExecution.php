<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrRunbookExecution extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'runbook_id', 'status', 'step_results', 'executed_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'step_results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function runbook(): BelongsTo
    {
        return $this->belongsTo(DrRunbook::class, 'runbook_id');
    }
}
