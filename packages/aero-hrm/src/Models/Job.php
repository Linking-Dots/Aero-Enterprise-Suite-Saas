<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'jobs_recruitment';

    protected $fillable = [
        'title',
        'department_id',
        'type',
        'location',
        'is_remote_allowed',
        'description',
        'responsibilities',
        'requirements',
        'qualifications',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_visible',
        'benefits',
        'posting_date',
        'closing_date',
        'status',
        'hiring_manager_id',
        'positions',
        'is_featured',
        'skills_required',
        'custom_fields',
        'created_by',
    ];

    protected $casts = [
        'is_remote_allowed' => 'boolean',
        'salary_visible' => 'boolean',
        'is_featured' => 'boolean',
        'posting_date' => 'date',
        'closing_date' => 'date',
        'responsibilities' => 'array',
        'requirements' => 'array',
        'qualifications' => 'array',
        'benefits' => 'array',
        'skills_required' => 'array',
        'custom_fields' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'positions' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function hiringManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    public function hiringStages(): HasMany
    {
        return $this->hasMany(JobHiringStage::class, 'job_id');
    }
}
