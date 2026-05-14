<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class College extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_colleges';

    protected $fillable = [
        'college_code', 'college_name', 'description', 'dean_id', 'location',
        'phone', 'email', 'website', 'budget', 'is_active', 'accreditation_info',
        'mission_statement', 'vision_statement', 'established_date', 'created_by',
    ];

    protected $casts = [
        'dean_id' => 'integer',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
        'accreditation_info' => 'json',
        'established_date' => 'date',
        'created_by' => 'integer',
    ];

    public function dean()
    {
        return $this->belongsTo(Faculty::class, 'dean_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function getDepartmentCountAttribute()
    {
        return $this->departments()->where('is_active', true)->count();
    }

    public function getTotalFacultyAttribute()
    {
        return Faculty::whereHas('department', function ($query) {
            $query->where('college_id', $this->id);
        })->where('status', Faculty::STATUS_ACTIVE)->count();
    }

    public function getTotalStudentsAttribute()
    {
        return Student::whereHas('department', function ($query) {
            $query->where('college_id', $this->id);
        })->where('student_status', Student::STATUS_ACTIVE)->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
