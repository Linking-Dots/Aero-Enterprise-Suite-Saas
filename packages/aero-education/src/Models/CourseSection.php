<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSection extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_course_sections';

    protected $fillable = [
        'course_id', 'semester_id', 'section_number', 'instructor_id',
        'room_id', 'max_enrollment', 'current_enrollment', 'credits',
        'meeting_pattern', 'start_date', 'end_date', 'status',
        'delivery_method', 'special_instructions', 'created_by',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'semester_id' => 'integer',
        'instructor_id' => 'integer',
        'room_id' => 'integer',
        'max_enrollment' => 'integer',
        'current_enrollment' => 'integer',
        'credits' => 'integer',
        'meeting_pattern' => 'json',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_by' => 'integer',
    ];

    const STATUS_OPEN = 'open';

    const STATUS_CLOSED = 'closed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_COMPLETED = 'completed';

    const STATUS_WAITLIST_ONLY = 'waitlist_only';

    const DELIVERY_IN_PERSON = 'in_person';

    const DELIVERY_ONLINE = 'online';

    const DELIVERY_HYBRID = 'hybrid';

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function semester()
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Faculty::class, 'instructor_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function getSectionCodeAttribute()
    {
        return $this->course->course_code.'-'.$this->section_number;
    }

    public function getFullSectionNameAttribute()
    {
        return $this->course->course_name.' (Section '.$this->section_number.')';
    }

    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->max_enrollment - $this->current_enrollment);
    }

    public function getWaitlistCountAttribute()
    {
        return $this->enrollments()
            ->where('status', Enrollment::STATUS_WAITLISTED)
            ->count();
    }

    public function getActiveEnrollmentsAttribute()
    {
        return $this->enrollments()
            ->where('status', Enrollment::STATUS_ENROLLED)
            ->get();
    }

    public function getTotalSessionsAttribute()
    {
        if (! is_array($this->meeting_pattern) || empty($this->meeting_pattern)) {
            return 0;
        }

        $totalSessions = 0;
        $startDate = $this->start_date;
        $endDate = $this->end_date;

        foreach ($this->meeting_pattern as $pattern) {
            if (isset($pattern['days']) && is_array($pattern['days'])) {
                $daysPerWeek = count($pattern['days']);
                $weeks = $startDate->diffInWeeks($endDate);
                $totalSessions += $daysPerWeek * $weeks;
            }
        }

        return $totalSessions;
    }

    public function hasAvailableSpots()
    {
        return $this->getAvailableSpotsAttribute() > 0;
    }

    public function isFull()
    {
        return $this->current_enrollment >= $this->max_enrollment;
    }

    public function canEnroll()
    {
        return $this->status === self::STATUS_OPEN && $this->hasAvailableSpots();
    }

    public function canWaitlist()
    {
        return in_array($this->status, [self::STATUS_WAITLIST_ONLY, self::STATUS_OPEN]) && $this->isFull();
    }

    public function addStudent(Student $student, $enrollmentType = Enrollment::TYPE_REGULAR)
    {
        if ($this->canEnroll()) {
            $enrollment = $this->enrollments()->create([
                'student_id' => $student->id,
                'semester_id' => $this->semester_id,
                'enrollment_date' => now(),
                'status' => Enrollment::STATUS_ENROLLED,
                'credit_hours' => $this->credits,
                'enrollment_type' => $enrollmentType,
                'affects_gpa' => true,
            ]);

            $this->increment('current_enrollment');

            return $enrollment;
        } elseif ($this->canWaitlist()) {
            $waitlistPosition = $this->getWaitlistCountAttribute() + 1;

            return $this->enrollments()->create([
                'student_id' => $student->id,
                'semester_id' => $this->semester_id,
                'enrollment_date' => now(),
                'status' => Enrollment::STATUS_WAITLISTED,
                'credit_hours' => $this->credits,
                'enrollment_type' => $enrollmentType,
                'waitlist_position' => $waitlistPosition,
                'affects_gpa' => true,
            ]);
        }

        return null;
    }

    public function processWaitlist()
    {
        while ($this->hasAvailableSpots()) {
            $nextWaitlistedStudent = $this->enrollments()
                ->where('status', Enrollment::STATUS_WAITLISTED)
                ->orderBy('waitlist_position')
                ->first();

            if (! $nextWaitlistedStudent || ! $nextWaitlistedStudent->moveFromWaitlist()) {
                break;
            }

            $this->increment('current_enrollment');
        }
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeCurrentSemester($query)
    {
        $currentSemester = AcademicSemester::current();

        return $currentSemester ? $query->where('semester_id', $currentSemester->id) : $query->whereRaw('1 = 0');
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
