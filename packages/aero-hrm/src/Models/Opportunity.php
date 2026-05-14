<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'type',
        'status',
        'application_deadline',
        'required_skills',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'date',
            'required_skills' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
