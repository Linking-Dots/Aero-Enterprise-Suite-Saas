<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafetyTraining extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'training_type',
        'training_date',
        'expiry_date',
        'is_required',
        'is_recurring',
        'recurrence_interval',
        'materials_url',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'training_date' => 'datetime',
        'expiry_date' => 'datetime',
        'is_required' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the employees associated with this safety training
     */
    public function employees()
    {
        return $this->belongsToMany(User::class, 'employee_safety_trainings')
            ->withPivot('completion_date', 'score', 'certificate_url', 'notes', 'status')
            ->withTimestamps();
    }

    /**
     * Get the department(s) this training is required for
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_safety_trainings')
            ->withTimestamps();
    }
}
