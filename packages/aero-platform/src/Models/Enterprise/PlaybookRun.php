<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybookRun extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'playbook_id', 'tenant_id', 'status', 'step_results', 'executed_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'step_results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function playbook(): BelongsTo
    {
        return $this->belongsTo(SuccessPlaybook::class, 'playbook_id');
    }
}
