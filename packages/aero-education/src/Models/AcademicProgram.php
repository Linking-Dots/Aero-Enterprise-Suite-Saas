<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicProgram extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_academic_programs';

    protected $fillable = [
        'program_code', 'program_name', 'description', 'department_id',
        'degree_type', 'degree_level', 'total_credit_hours', 'duration_semesters',
        'admission_requirements', 'curriculum_requirements', 'graduation_requirements',
        'is_active', 'accreditation_status', 'program_director_id', 'created_by',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'total_credit_hours' => 'integer',
        'duration_semesters' => 'integer',
        'admission_requirements' => 'json',
        'curriculum_requirements' => 'json',
        'graduation_requirements' => 'json',
        'is_active' => 'boolean',
        'program_director_id' => 'integer',
        'created_by' => 'integer',
    ];

    const TYPE_MAJOR = 'major';

    const TYPE_MINOR = 'minor';

    const TYPE_CONCENTRATION = 'concentration';

    const TYPE_CERTIFICATE = 'certificate';

    const LEVEL_ASSOCIATE = 'associate';

    const LEVEL_BACHELOR = 'bachelor';

    const LEVEL_MASTER = 'master';

    const LEVEL_DOCTORAL = 'doctoral';

    const LEVEL_CERTIFICATE = 'certificate';

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function programDirector()
    {
        return $this->belongsTo(Faculty::class, 'program_director_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'major');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'program_courses')
            ->withPivot('requirement_type', 'is_required');
    }

    public function getEnrollmentCountAttribute()
    {
        return $this->students()->where('student_status', Student::STATUS_ACTIVE)->count();
    }

    public function checkGraduationRequirements(Student $student)
    {
        $requirements = $this->graduation_requirements ?? [];
        $results = [];

        // Check total credit hours
        if (isset($requirements['total_credit_hours'])) {
            $results['credit_hours'] = [
                'required' => $requirements['total_credit_hours'],
                'completed' => $student->credit_hours_completed,
                'met' => $student->credit_hours_completed >= $requirements['total_credit_hours'],
            ];
        }

        // Check minimum GPA
        if (isset($requirements['minimum_gpa'])) {
            $results['gpa'] = [
                'required' => $requirements['minimum_gpa'],
                'current' => $student->gpa,
                'met' => $student->gpa >= $requirements['minimum_gpa'],
            ];
        }

        // Check residency requirements (minimum credits at institution)
        if (isset($requirements['residency_credits'])) {
            $residencyCredits = $student->credit_hours_completed; // Simplified
            $results['residency'] = [
                'required' => $requirements['residency_credits'],
                'completed' => $residencyCredits,
                'met' => $residencyCredits >= $requirements['residency_credits'],
            ];
        }

        return $results;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByDegreeLevel($query, $level)
    {
        return $query->where('degree_level', $level);
    }
}
