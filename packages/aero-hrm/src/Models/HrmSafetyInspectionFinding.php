<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmSafetyInspectionFinding extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_inspection_findings';

    protected $fillable = [
        'inspection_id',
        'category',
        'description',
        'severity',
        'status',
        'due_date',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(HrmSafetyInspection::class, 'inspection_id');
    }
}
