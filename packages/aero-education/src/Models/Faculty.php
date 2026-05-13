<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_faculty';

    protected $fillable = [
        'faculty_id', 'user_id', 'first_name', 'last_name', 'middle_name',
        'email', 'phone', 'office_location', 'office_hours', 'department_id',
        'title', 'rank', 'employment_type', 'hire_date', 'tenure_date',
        'specializations', 'education', 'certifications', 'bio',
        'research_interests', 'publications', 'is_advisor', 'max_advisees',
        'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'department_id' => 'integer',
        'hire_date' => 'date',
        'tenure_date' => 'date',
        'specializations' => 'json',
        'education' => 'json',
        'certifications' => 'json',
        'research_interests' => 'json',
        'publications' => 'json',
        'is_advisor' => 'boolean',
        'max_advisees' => 'integer',
        'created_by' => 'integer',
    ];

    const TITLE_PROFESSOR = 'professor';

    const TITLE_ASSOCIATE_PROFESSOR = 'associate_professor';

    const TITLE_ASSISTANT_PROFESSOR = 'assistant_professor';

    const TITLE_INSTRUCTOR = 'instructor';

    const TITLE_LECTURER = 'lecturer';

    const TITLE_ADJUNCT = 'adjunct';

    const TITLE_VISITING = 'visiting';

    const TITLE_EMERITUS = 'emeritus';

    const RANK_FULL = 'full';

    const RANK_ASSOCIATE = 'associate';

    const RANK_ASSISTANT = 'assistant';

    const RANK_CLINICAL = 'clinical';

    const RANK_RESEARCH = 'research';

    const EMPLOYMENT_FULL_TIME = 'full_time';

    const EMPLOYMENT_PART_TIME = 'part_time';

    const EMPLOYMENT_ADJUNCT = 'adjunct';

    const EMPLOYMENT_VISITING = 'visiting';

    const EMPLOYMENT_EMERITUS = 'emeritus';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SABBATICAL = 'sabbatical';

    const STATUS_RETIRED = 'retired';

    const STATUS_TERMINATED = 'terminated';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courseSections()
    {
        return $this->hasMany(CourseSection::class, 'instructor_id');
    }

    public function advisees()
    {
        return $this->hasMany(Student::class, 'advisor_id');
    }

    public function committees()
    {
        return $this->belongsToMany(Committee::class, 'committee_members')
            ->withPivot('role', 'start_date', 'end_date');
    }

    public function evaluations()
    {
        return $this->hasMany(FacultyEvaluation::class);
    }

    public function getFullNameAttribute()
    {
        $name = trim($this->first_name.' '.($this->middle_name ? $this->middle_name.' ' : '').$this->last_name);

        return $name;
    }

    public function getFullTitleAttribute()
    {
        $title = str_replace('_', ' ', ucwords($this->title, '_'));

        return $title.' '.$this->getFullNameAttribute();
    }

    public function getCurrentTeachingLoadAttribute()
    {
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return 0;
        }

        return $this->courseSections()
            ->where('semester_id', $currentSemester->id)
            ->sum('credit_hours');
    }

    public function getCurrentAdviseesCountAttribute()
    {
        return $this->advisees()->where('student_status', Student::STATUS_ACTIVE)->count();
    }

    public function getYearsOfServiceAttribute()
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }

    public function isTenured()
    {
        return $this->tenure_date && $this->tenure_date <= now();
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canTakeMoreAdvisees()
    {
        if (! $this->is_advisor || ! $this->max_advisees) {
            return false;
        }

        return $this->getCurrentAdviseesCountAttribute() < $this->max_advisees;
    }

    public function getAverageTeachingEvaluationAttribute()
    {
        $evaluations = $this->evaluations()
            ->where('evaluation_type', FacultyEvaluation::TYPE_STUDENT_TEACHING)
            ->where('status', FacultyEvaluation::STATUS_COMPLETED);

        return $evaluations->count() > 0 ? round($evaluations->avg('overall_rating'), 2) : null;
    }

    public function getPublicationsCountAttribute()
    {
        return is_array($this->publications) ? count($this->publications) : 0;
    }

    public function hasSpecialization($specialization)
    {
        return is_array($this->specializations) && in_array($specialization, $this->specializations);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', $title);
    }

    public function scopeAdvisors($query)
    {
        return $query->where('is_advisor', true);
    }

    public function scopeAvailableAdvisors($query)
    {
        return $query->where('is_advisor', true)
            ->whereRaw('(SELECT COUNT(*) FROM education_students WHERE advisor_id = education_faculty.id AND student_status = ?) < max_advisees', [Student::STATUS_ACTIVE]);
    }

    public function scopeTenured($query)
    {
        return $query->whereNotNull('tenure_date')
            ->where('tenure_date', '<=', now());
    }
}
