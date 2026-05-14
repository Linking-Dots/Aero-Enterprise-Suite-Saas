<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSemester extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_academic_semesters';

    protected $fillable = [
        'semester_name', 'academic_year', 'semester_type', 'start_date',
        'end_date', 'registration_start', 'registration_end', 'add_drop_deadline',
        'withdraw_deadline', 'final_exam_start', 'final_exam_end',
        'graduation_date', 'is_active', 'is_current', 'status', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start' => 'date',
        'registration_end' => 'date',
        'add_drop_deadline' => 'date',
        'withdraw_deadline' => 'date',
        'final_exam_start' => 'date',
        'final_exam_end' => 'date',
        'graduation_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_FALL = 'fall';

    const TYPE_SPRING = 'spring';

    const TYPE_SUMMER = 'summer';

    const TYPE_WINTER = 'winter';

    const TYPE_INTERSESSION = 'intersession';

    const STATUS_PLANNING = 'planning';

    const STATUS_REGISTRATION = 'registration';

    const STATUS_ACTIVE = 'active';

    const STATUS_FINALS = 'finals';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courseSections()
    {
        return $this->hasMany(CourseSection::class, 'semester_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'semester_id');
    }

    public function financialAidDisbursements()
    {
        return $this->hasMany(FinancialAidDisbursement::class, 'semester_id');
    }

    public function getFullNameAttribute()
    {
        return $this->semester_name.' '.$this->academic_year;
    }

    public function getDurationInWeeksAttribute()
    {
        return $this->start_date->diffInWeeks($this->end_date);
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->end_date > now()) {
            return now()->diffInDays($this->end_date);
        }

        return 0;
    }

    public function getTotalEnrollmentAttribute()
    {
        return $this->enrollments()
            ->where('status', Enrollment::STATUS_ENROLLED)
            ->count();
    }

    public function getTotalSectionsAttribute()
    {
        return $this->courseSections()
            ->where('status', CourseSection::STATUS_OPEN)
            ->count();
    }

    public function isRegistrationOpen()
    {
        $today = now()->toDateString();

        return $this->registration_start <= $today && $this->registration_end >= $today;
    }

    public function isAddDropPeriod()
    {
        $today = now()->toDateString();

        return $this->start_date <= $today && $this->add_drop_deadline >= $today;
    }

    public function isWithdrawPeriod()
    {
        $today = now()->toDateString();

        return $this->add_drop_deadline < $today && $this->withdraw_deadline >= $today;
    }

    public function isCurrentSemester()
    {
        $today = now()->toDateString();

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function isFinalExamPeriod()
    {
        $today = now()->toDateString();

        return $this->final_exam_start <= $today && $this->final_exam_end >= $today;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED || $this->end_date < now();
    }

    public function getRegistrationStatus()
    {
        $today = now()->toDateString();

        if ($today < $this->registration_start) {
            return 'Registration Not Yet Open';
        } elseif ($today <= $this->registration_end) {
            return 'Registration Open';
        } else {
            return 'Registration Closed';
        }
    }

    public function getAcademicPeriodStatus()
    {
        $today = now()->toDateString();

        if ($today < $this->start_date) {
            return 'Upcoming';
        } elseif ($today <= $this->add_drop_deadline) {
            return 'Add/Drop Period';
        } elseif ($today <= $this->withdraw_deadline) {
            return 'Withdrawal Period';
        } elseif ($today < $this->final_exam_start) {
            return 'Regular Classes';
        } elseif ($today <= $this->final_exam_end) {
            return 'Final Exams';
        } else {
            return 'Completed';
        }
    }

    public static function current()
    {
        return static::where('is_current', true)
            ->where('is_active', true)
            ->first();
    }

    public static function upcoming()
    {
        return static::where('start_date', '>', now())
            ->where('is_active', true)
            ->orderBy('start_date')
            ->first();
    }

    public function makeCurrentSemester()
    {
        // Set all other semesters as not current
        static::where('is_current', true)->update(['is_current' => false]);

        // Set this semester as current
        $this->is_current = true;
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('semester_type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->orderBy('start_date');
    }

    public function scopeCompleted($query)
    {
        return $query->where('end_date', '<', now());
    }
}
