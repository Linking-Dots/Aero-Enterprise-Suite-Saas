<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDependency extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'predecessor_id',
        'successor_id',
        'dependency_type',
        'lag_days',
    ];

    protected $casts = [
        'lag_days' => 'integer',
    ];

    /**
     * Get the predecessor task.
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'predecessor_id');
    }

    /**
     * Get the successor task.
     */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'successor_id');
    }

    /**
     * Get dependency type options.
     */
    public static function getDependencyTypeOptions(): array
    {
        return [
            'finish_to_start' => 'Finish to Start',
            'start_to_start' => 'Start to Start',
            'finish_to_finish' => 'Finish to Finish',
            'start_to_finish' => 'Start to Finish',
        ];
    }

    /**
     * Get dependency type description.
     */
    public function getDependencyTypeDescriptionAttribute(): string
    {
        $descriptions = [
            'finish_to_start' => 'Successor task cannot start until predecessor task finishes',
            'start_to_start' => 'Successor task cannot start until predecessor task starts',
            'finish_to_finish' => 'Successor task cannot finish until predecessor task finishes',
            'start_to_finish' => 'Successor task cannot finish until predecessor task starts',
        ];

        return $descriptions[$this->dependency_type] ?? 'Unknown dependency type';
    }
}
