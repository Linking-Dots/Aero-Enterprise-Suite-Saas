<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_enrollments';

    protected $fillable = [
        'student_id', 'course_section_id', 'semester_id', 'enrollment_date',
        'status', 'grade_mode', 'credit_hours', 'enrollment_type',
        'waitlist_position', 'drop_date', 'add_date', 'last_attendance_date',
        'midterm_grade', 'final_grade', 'grade_points', 'affects_gpa',
        'notes', 'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'course_section_id' => 'integer',
        'semester_id' => 'integer',
        'enrollment_date' => 'date',
        'drop_date' => 'date',
        'add_date' => 'date',
        'last_attendance_date' => 'date',
        'credit_hours' => 'integer',
        'waitlist_position' => 'integer',
        'grade_points' => 'decimal:3',
        'affects_gpa' => 'boolean',
        'created_by' => 'integer',
    ];

    const STATUS_ENROLLED = 'enrolled';

    const STATUS_WAITLISTED = 'waitlisted';

    const STATUS_DROPPED = 'dropped';

    const STATUS_WITHDRAWN = 'withdrawn';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_INCOMPLETE = 'incomplete';

    const STATUS_AUDIT = 'audit';

    const GRADE_MODE_LETTER = 'letter';

    const GRADE_MODE_PASS_FAIL = 'pass_fail';

    const GRADE_MODE_AUDIT = 'audit';

    const GRADE_MODE_CREDIT_NO_CREDIT = 'credit_no_credit';

    const TYPE_REGULAR = 'regular';

    const TYPE_LATE_ADD = 'late_add';

    const TYPE_OVERRIDE = 'override';

    const TYPE_INDEPENDENT_STUDY = 'independent_study';

    const TYPE_INTERNSHIP = 'internship';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function semester()
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    public function isWithdrawn()
    {
        return in_array($this->status, [self::STATUS_DROPPED, self::STATUS_WITHDRAWN]);
    }

    public function canDrop()
    {
        if (! $this->isActive()) {
            return false;
        }

        $semester = $this->semester;
        if (! $semester) {
            return false;
        }

        // Can drop until 60% of semester is complete
        $semesterLength = $semester->start_date->diffInDays($semester->end_date);
        $dropDeadline = $semester->start_date->addDays($semesterLength * 0.6);

        return now()->toDateString() <= $dropDeadline->toDateString();
    }

    public function canWithdraw()
    {
        if (! $this->isActive()) {
            return false;
        }

        $semester = $this->semester;
        if (! $semester) {
            return false;
        }

        // Can withdraw until 80% of semester is complete
        $semesterLength = $semester->start_date->diffInDays($semester->end_date);
        $withdrawDeadline = $semester->start_date->addDays($semesterLength * 0.8);

        return now()->toDateString() <= $withdrawDeadline->toDateString();
    }

    public function getCurrentGradeAttribute()
    {
        return $this->final_grade ?: $this->midterm_grade;
    }

    public function getAttendancePercentageAttribute()
    {
        $totalSessions = $this->courseSection->getTotalSessionsAttribute();
        if ($totalSessions === 0) {
            return 100;
        }

        $attendedSessions = $this->attendanceRecords()
            ->where('status', AttendanceRecord::STATUS_PRESENT)
            ->count();

        return round(($attendedSessions / $totalSessions) * 100, 2);
    }

    public function getAssignmentGradesAttribute()
    {
        return $this->grades()
            ->where('grade_type', '!=', Grade::TYPE_FINAL)
            ->orderBy('created_at')
            ->get();
    }

    public function calculateCurrentGrade()
    {
        $grades = $this->grades;
        if ($grades->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $totalPossible = 0;

        foreach ($grades as $grade) {
            $totalPoints += $grade->points_earned;
            $totalPossible += $grade->points_possible;
        }

        return $totalPossible > 0 ? round(($totalPoints / $totalPossible) * 100, 2) : null;
    }

    public function moveFromWaitlist()
    {
        if ($this->status !== self::STATUS_WAITLISTED) {
            return false;
        }

        $section = $this->courseSection;
        if ($section && $section->hasAvailableSpots()) {
            $this->update([
                'status' => self::STATUS_ENROLLED,
                'enrollment_date' => now(),
                'waitlist_position' => null,
            ]);

            return true;
        }

        return false;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ENROLLED);
    }

    public function scopeCurrentSemester($query)
    {
        $currentSemester = AcademicSemester::current();

        return $currentSemester ? $query->where('semester_id', $currentSemester->id) : $query->whereRaw('1 = 0');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('course_section_id', $sectionId);
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', self::STATUS_WAITLISTED)
            ->orderBy('waitlist_position');
    }
}
