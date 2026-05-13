<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transcript extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_transcripts';

    protected $fillable = [
        'student_id', 'transcript_type', 'academic_level', 'generated_date',
        'requested_date', 'delivered_date', 'delivery_method', 'recipient_name',
        'recipient_address', 'recipient_email', 'status', 'holds_preventing_release',
        'fee_amount', 'fee_paid', 'verification_code', 'digital_signature',
        'seal_applied', 'notes', 'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'generated_date' => 'date',
        'requested_date' => 'date',
        'delivered_date' => 'date',
        'holds_preventing_release' => 'json',
        'fee_amount' => 'decimal:2',
        'fee_paid' => 'boolean',
        'seal_applied' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_OFFICIAL = 'official';

    const TYPE_UNOFFICIAL = 'unofficial';

    const TYPE_PARTIAL = 'partial';

    const TYPE_IN_PROGRESS = 'in_progress';

    const LEVEL_UNDERGRADUATE = 'undergraduate';

    const LEVEL_GRADUATE = 'graduate';

    const LEVEL_DOCTORAL = 'doctoral';

    const LEVEL_ALL = 'all';

    const STATUS_REQUESTED = 'requested';

    const STATUS_PROCESSING = 'processing';

    const STATUS_READY = 'ready';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_CANCELLED = 'cancelled';

    const DELIVERY_MAIL = 'mail';

    const DELIVERY_EMAIL = 'email';

    const DELIVERY_PICKUP = 'pickup';

    const DELIVERY_ELECTRONIC = 'electronic';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transcriptEntries()
    {
        return $this->hasMany(TranscriptEntry::class);
    }

    public function isOfficial()
    {
        return $this->transcript_type === self::TYPE_OFFICIAL;
    }

    public function isReady()
    {
        return $this->status === self::STATUS_READY;
    }

    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function hasHolds()
    {
        return ! empty($this->holds_preventing_release);
    }

    public function canRelease()
    {
        return ! $this->hasHolds() && ($this->fee_paid || $this->fee_amount == 0);
    }

    public function generateTranscript()
    {
        if (! $this->canRelease()) {
            return false;
        }

        $student = $this->student;
        $entries = [];

        // Get all completed enrollments for the student
        $enrollments = $student->enrollments()
            ->with(['courseSection.course', 'semester', 'grades'])
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->orderBy('semester_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            $finalGrade = $enrollment->grades()->where('is_final', true)->first();
            if ($finalGrade) {
                $entries[] = [
                    'semester' => $enrollment->semester->getFullNameAttribute(),
                    'course_code' => $enrollment->courseSection->course->getFullCourseCodeAttribute(),
                    'course_name' => $enrollment->courseSection->course->course_name,
                    'credit_hours' => $enrollment->credit_hours,
                    'grade' => $finalGrade->letter_grade,
                    'grade_points' => $finalGrade->grade_points,
                ];
            }
        }

        // Create transcript entries
        foreach ($entries as $entry) {
            $this->transcriptEntries()->create($entry);
        }

        $this->update([
            'generated_date' => now(),
            'status' => self::STATUS_READY,
            'verification_code' => $this->generateVerificationCode(),
        ]);

        return true;
    }

    public function calculateGPA($level = null)
    {
        $query = $this->transcriptEntries();

        if ($level) {
            // Filter by course level if specified
            $query->whereHas('course', function ($q) use ($level) {
                $q->where('course_level', 'like', $level.'%');
            });
        }

        $entries = $query->whereNotNull('grade_points')->get();

        if ($entries->isEmpty()) {
            return 0.000;
        }

        $totalGradePoints = 0;
        $totalCreditHours = 0;

        foreach ($entries as $entry) {
            $totalGradePoints += $entry->grade_points * $entry->credit_hours;
            $totalCreditHours += $entry->credit_hours;
        }

        return $totalCreditHours > 0 ? round($totalGradePoints / $totalCreditHours, 3) : 0.000;
    }

    public function getTotalCreditsAttribute()
    {
        return $this->transcriptEntries()->sum('credit_hours');
    }

    public function getOverallGPAAttribute()
    {
        return $this->calculateGPA();
    }

    public function getProcessingTimeAttribute()
    {
        if ($this->requested_date && $this->generated_date) {
            return $this->requested_date->diffInDays($this->generated_date);
        }

        return null;
    }

    private function generateVerificationCode()
    {
        return strtoupper(substr(md5($this->student_id.$this->generated_date.rand()), 0, 8));
    }

    public function scopeOfficial($query)
    {
        return $query->where('transcript_type', self::TYPE_OFFICIAL);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_REQUESTED, self::STATUS_PROCESSING]);
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', self::STATUS_ON_HOLD);
    }
}
