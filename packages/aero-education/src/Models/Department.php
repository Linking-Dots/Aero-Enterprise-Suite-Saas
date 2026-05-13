<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_departments';

    protected $fillable = [
        'department_code', 'department_name', 'description', 'college_id',
        'department_head_id', 'location', 'phone', 'email', 'website',
        'budget', 'is_active', 'accreditation_info', 'mission_statement',
        'established_date', 'created_by',
    ];

    protected $casts = [
        'college_id' => 'integer',
        'department_head_id' => 'integer',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
        'accreditation_info' => 'json',
        'established_date' => 'date',
        'created_by' => 'integer',
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function departmentHead()
    {
        return $this->belongsTo(Faculty::class, 'department_head_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function faculty()
    {
        return $this->hasMany(Faculty::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function majors()
    {
        return $this->hasMany(AcademicProgram::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'major_department_id');
    }

    public function getFacultyCountAttribute()
    {
        return $this->faculty()->where('status', Faculty::STATUS_ACTIVE)->count();
    }

    public function getActiveCoursesCountAttribute()
    {
        return $this->courses()->where('is_active', true)->count();
    }

    public function getStudentMajorsCountAttribute()
    {
        return $this->students()->where('student_status', Student::STATUS_ACTIVE)->count();
    }

    public function getCurrentEnrollmentAttribute()
    {
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return 0;
        }

        return Enrollment::whereHas('courseSection.course', function ($query) {
            $query->where('department_id', $this->id);
        })->where('semester_id', $currentSemester->id)
            ->where('status', Enrollment::STATUS_ENROLLED)
            ->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCollege($query, $collegeId)
    {
        return $query->where('college_id', $collegeId);
    }
}
