<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAid extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_financial_aid';

    protected $fillable = [
        'student_id', 'academic_year', 'aid_type', 'aid_source', 'award_name',
        'awarded_amount', 'disbursed_amount', 'remaining_amount', 'eligibility_status',
        'application_date', 'award_date', 'acceptance_date', 'expiration_date',
        'renewable', 'renewal_criteria', 'gpa_requirement', 'enrollment_requirement',
        'satisfactory_progress', 'notes', 'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'awarded_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'application_date' => 'date',
        'award_date' => 'date',
        'acceptance_date' => 'date',
        'expiration_date' => 'date',
        'renewable' => 'boolean',
        'renewal_criteria' => 'json',
        'gpa_requirement' => 'decimal:3',
        'enrollment_requirement' => 'integer',
        'satisfactory_progress' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_GRANT = 'grant';

    const TYPE_SCHOLARSHIP = 'scholarship';

    const TYPE_LOAN = 'loan';

    const TYPE_WORK_STUDY = 'work_study';

    const TYPE_FELLOWSHIP = 'fellowship';

    const TYPE_ASSISTANTSHIP = 'assistantship';

    const SOURCE_FEDERAL = 'federal';

    const SOURCE_STATE = 'state';

    const SOURCE_INSTITUTIONAL = 'institutional';

    const SOURCE_PRIVATE = 'private';

    const SOURCE_EMPLOYER = 'employer';

    const SOURCE_FOUNDATION = 'foundation';

    const STATUS_ELIGIBLE = 'eligible';

    const STATUS_AWARDED = 'awarded';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_DECLINED = 'declined';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_COMPLETED = 'completed';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disbursements()
    {
        return $this->hasMany(FinancialAidDisbursement::class);
    }

    public function isActive()
    {
        return in_array($this->eligibility_status, [self::STATUS_AWARDED, self::STATUS_ACCEPTED]);
    }

    public function isExpired()
    {
        return $this->expiration_date && $this->expiration_date < now();
    }

    public function isRenewable()
    {
        return $this->renewable && ! $this->isExpired();
    }

    public function meetsRenewalCriteria()
    {
        $student = $this->student;
        if (! $student) {
            return false;
        }

        // Check GPA requirement
        if ($this->gpa_requirement && $student->gpa < $this->gpa_requirement) {
            return false;
        }

        // Check enrollment requirement
        if ($this->enrollment_requirement) {
            $currentEnrollment = $student->getCurrentSemesterEnrollmentsAttribute()->sum('credit_hours');
            if ($currentEnrollment < $this->enrollment_requirement) {
                return false;
            }
        }

        // Check satisfactory academic progress
        if ($this->satisfactory_progress === false) {
            return false;
        }

        return true;
    }

    public function calculateDisbursementSchedule($numberOfDisbursements = 2)
    {
        if (! $this->isActive() || $this->awarded_amount <= 0) {
            return [];
        }

        $amountPerDisbursement = $this->awarded_amount / $numberOfDisbursements;
        $schedule = [];

        // Get current semester
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return [];
        }

        // Create disbursement schedule
        for ($i = 0; $i < $numberOfDisbursements; $i++) {
            $disbursementDate = $currentSemester->start_date->addDays($i * 60); // Every 2 months
            $schedule[] = [
                'disbursement_number' => $i + 1,
                'disbursement_date' => $disbursementDate,
                'amount' => $amountPerDisbursement,
            ];
        }

        return $schedule;
    }

    public function getTotalDisbursedAttribute()
    {
        return $this->disbursements()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->awarded_amount - $this->getTotalDisbursedAttribute();
    }

    public function getDisbursementPercentageAttribute()
    {
        if ($this->awarded_amount <= 0) {
            return 0;
        }

        return round(($this->getTotalDisbursedAttribute() / $this->awarded_amount) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('eligibility_status', [self::STATUS_AWARDED, self::STATUS_ACCEPTED]);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('aid_type', $type);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('aid_source', $source);
    }

    public function scopeRenewable($query)
    {
        return $query->where('renewable', true);
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now());
    }
}
