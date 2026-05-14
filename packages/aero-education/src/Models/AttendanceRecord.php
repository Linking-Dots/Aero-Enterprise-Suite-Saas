<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_attendance_records';

    protected $fillable = [
        'student_id', 'enrollment_id', 'class_session_id', 'attendance_date',
        'status', 'check_in_time', 'check_out_time', 'minutes_attended',
        'excuse_reason', 'excuse_approved', 'approved_by', 'notes',
        'location_verified', 'ip_address', 'device_info', 'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'enrollment_id' => 'integer',
        'class_session_id' => 'integer',
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'minutes_attended' => 'integer',
        'excuse_approved' => 'boolean',
        'approved_by' => 'integer',
        'location_verified' => 'boolean',
        'device_info' => 'json',
        'created_by' => 'integer',
    ];

    const STATUS_PRESENT = 'present';

    const STATUS_ABSENT = 'absent';

    const STATUS_LATE = 'late';

    const STATUS_EXCUSED = 'excused';

    const STATUS_TARDY = 'tardy';

    const STATUS_LEFT_EARLY = 'left_early';

    const EXCUSE_ILLNESS = 'illness';

    const EXCUSE_FAMILY_EMERGENCY = 'family_emergency';

    const EXCUSE_MEDICAL_APPOINTMENT = 'medical_appointment';

    const EXCUSE_RELIGIOUS_OBSERVANCE = 'religious_observance';

    const EXCUSE_COURT_APPEARANCE = 'court_appearance';

    const EXCUSE_MILITARY_SERVICE = 'military_service';

    const EXCUSE_ACADEMIC_CONFLICT = 'academic_conflict';

    const EXCUSE_TRANSPORTATION = 'transportation';

    const EXCUSE_OTHER = 'other';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPresent()
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_TARDY]);
    }

    public function isAbsent()
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function isExcused()
    {
        return $this->status === self::STATUS_EXCUSED || $this->excuse_approved;
    }

    public function isLate()
    {
        return in_array($this->status, [self::STATUS_LATE, self::STATUS_TARDY]);
    }

    public function getAttendanceDurationAttribute()
    {
        if ($this->check_in_time && $this->check_out_time) {
            return $this->check_in_time->diffInMinutes($this->check_out_time);
        }

        return $this->minutes_attended;
    }

    public function getLatenessAttribute()
    {
        if (! $this->classSession || ! $this->check_in_time) {
            return 0;
        }

        $classStartTime = $this->classSession->start_time;

        return $this->check_in_time > $classStartTime ?
               $classStartTime->diffInMinutes($this->check_in_time) : 0;
    }

    public function getEarlyDepartureAttribute()
    {
        if (! $this->classSession || ! $this->check_out_time) {
            return 0;
        }

        $classEndTime = $this->classSession->end_time;

        return $this->check_out_time < $classEndTime ?
               $this->check_out_time->diffInMinutes($classEndTime) : 0;
    }

    public function calculateStatus()
    {
        if (! $this->check_in_time) {
            return self::STATUS_ABSENT;
        }

        $lateness = $this->getLatenessAttribute();
        $earlyDeparture = $this->getEarlyDepartureAttribute();

        if ($lateness > 15) { // More than 15 minutes late
            return self::STATUS_LATE;
        } elseif ($lateness > 5) { // 5-15 minutes late
            return self::STATUS_TARDY;
        } elseif ($earlyDeparture > 15) { // Left more than 15 minutes early
            return self::STATUS_LEFT_EARLY;
        } else {
            return self::STATUS_PRESENT;
        }
    }

    public function autoUpdateStatus()
    {
        if (! $this->isExcused()) {
            $this->status = $this->calculateStatus();
            $this->save();
        }
    }

    public function submitExcuseRequest($reason, $documentation = null)
    {
        $this->excuse_reason = $reason;
        $this->excuse_approved = null; // Reset approval status
        $this->save();

        // Notify relevant faculty/administration
        // This would typically trigger an email or notification

        return true;
    }

    public function approveExcuse(User $approver)
    {
        $this->excuse_approved = true;
        $this->approved_by = $approver->id;
        $this->status = self::STATUS_EXCUSED;
        $this->save();
    }

    public function denyExcuse(User $approver)
    {
        $this->excuse_approved = false;
        $this->approved_by = $approver->id;
        $this->save();
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_TARDY]);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeExcused($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXCUSED)
                ->orWhere('excuse_approved', true);
        });
    }

    public function scopeLate($query)
    {
        return $query->whereIn('status', [self::STATUS_LATE, self::STATUS_TARDY]);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopePendingExcuse($query)
    {
        return $query->whereNotNull('excuse_reason')
            ->whereNull('excuse_approved');
    }
}
