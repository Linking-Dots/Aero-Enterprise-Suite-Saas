<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttritionRiskScore extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_attrition_risk_scores';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'employee_id',
        'score',
        'band',
        'factors',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'factors' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
