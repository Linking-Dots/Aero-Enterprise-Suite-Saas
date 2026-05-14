<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_courses';

    protected $fillable = [
        'course_code', 'course_name', 'course_description', 'department_id',
        'credit_hours', 'contact_hours', 'course_level', 'prerequisites',
        'corequisites', 'learning_objectives', 'syllabus', 'is_active',
        'max_enrollment', 'course_type', 'delivery_method', 'created_by',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'credit_hours' => 'integer',
        'contact_hours' => 'integer',
        'prerequisites' => 'json',
        'corequisites' => 'json',
        'learning_objectives' => 'json',
        'is_active' => 'boolean',
        'max_enrollment' => 'integer',
        'created_by' => 'integer',
    ];

    const LEVEL_100 = '100'; // Freshman

    const LEVEL_200 = '200'; // Sophomore

    const LEVEL_300 = '300'; // Junior

    const LEVEL_400 = '400'; // Senior

    const LEVEL_500 = '500'; // Graduate

    const LEVEL_600 = '600'; // Graduate Advanced

    const LEVEL_700 = '700'; // Doctoral

    const TYPE_CORE = 'core';

    const TYPE_ELECTIVE = 'elective';

    const TYPE_MAJOR_REQUIRED = 'major_required';

    const TYPE_GENERAL_EDUCATION = 'general_education';

    const TYPE_CAPSTONE = 'capstone';

    const TYPE_INTERNSHIP = 'internship';

    const TYPE_INDEPENDENT_STUDY = 'independent_study';

    const DELIVERY_IN_PERSON = 'in_person';

    const DELIVERY_ONLINE = 'online';

    const DELIVERY_HYBRID = 'hybrid';

    const DELIVERY_SYNCHRONOUS = 'synchronous';

    const DELIVERY_ASYNCHRONOUS = 'asynchronous';

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class);
    }

    public function enrollments()
    {
        return $this->hasManyThrough(Enrollment::class, CourseSection::class);
    }

    public function assignments()
    {
        return $this->hasManyThrough(Assignment::class, CourseSection::class);
    }

    public function prerequisites()
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id');
    }

    public function dependentCourses()
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'prerequisite_course_id', 'course_id');
    }

    public function getFullCourseCodeAttribute()
    {
        return $this->department ? $this->department->code.' '.$this->course_code : $this->course_code;
    }

    public function getCurrentSectionsAttribute()
    {
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return collect();
        }

        return $this->sections()
            ->where('semester_id', $currentSemester->id)
            ->get();
    }

    public function getTotalEnrollmentAttribute()
    {
        return $this->enrollments()->where('status', Enrollment::STATUS_ENROLLED)->count();
    }

    public function getAverageGradeAttribute()
    {
        $grades = Grade::whereHas('enrollment.section', function ($query) {
            $query->where('course_id', $this->id);
        })->where('is_final', true)->get();

        if ($grades->isEmpty()) {
            return null;
        }

        return round($grades->avg('grade_points'), 2);
    }

    public function hasPrerequisite($courseId)
    {
        return $this->prerequisites()->where('prerequisite_course_id', $courseId)->exists();
    }

    public function checkPrerequisites(Student $student)
    {
        $completedCourses = $student->grades()
            ->where('is_final', true)
            ->where('grade_points', '>=', 2.0) // Assuming C grade minimum
            ->pluck('course_id')
            ->toArray();

        $missingPrerequisites = [];

        foreach ($this->prerequisites as $prerequisite) {
            if (! in_array($prerequisite->id, $completedCourses)) {
                $missingPrerequisites[] = $prerequisite;
            }
        }

        return $missingPrerequisites;
    }

    public function isAvailableForLevel($academicLevel)
    {
        $levelMappings = [
            Student::LEVEL_UNDERGRADUATE => [self::LEVEL_100, self::LEVEL_200, self::LEVEL_300, self::LEVEL_400],
            Student::LEVEL_GRADUATE => [self::LEVEL_500, self::LEVEL_600],
            Student::LEVEL_DOCTORAL => [self::LEVEL_700],
        ];

        return isset($levelMappings[$academicLevel]) &&
               in_array($this->course_level, $levelMappings[$academicLevel]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('course_level', $level);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('course_type', $type);
    }

    public function scopeByDeliveryMethod($query, $method)
    {
        return $query->where('delivery_method', $method);
    }
}
