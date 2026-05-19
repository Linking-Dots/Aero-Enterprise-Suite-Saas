<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmCareerMilestone extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_career_milestones';

    protected $fillable = [
        'career_path_id',
        'name',
        'order_index',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'order_index' => 'integer',
        ];
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(HrmCareerPath::class, 'career_path_id');
    }
}
