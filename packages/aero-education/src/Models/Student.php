<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Student extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_students';

    protected $fillable = [
        'student_id', 'user_id', 'first_name', 'last_name', 'middle_name',
        'date_of_birth', 'gender', 'email', 'phone', 'emergency_contact',
        'emergency_phone', 'address_line_1', 'address_line_2', 'city',
        'state', 'postal_code', 'country', 'nationality', 'ssn_encrypted',
        'admission_date', 'graduation_date', 'student_status', 'academic_level',
        'major', 'minor', 'advisor_id', 'gpa', 'credit_hours_completed',
        'credit_hours_attempted', 'expected_graduation', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'graduation_date' => 'date',
        'advisor_id' => 'integer',
        'gpa' => 'decimal:3',
        'credit_hours_completed' => 'integer',
        'credit_hours_attempted' => 'integer',
        'expected_graduation' => 'date',
        'created_by' => 'integer',
    ];

    protected $hidden = ['ssn_encrypted'];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_GRADUATED = 'graduated';

    const STATUS_WITHDRAWN = 'withdrawn';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_EXPELLED = 'expelled';

    const STATUS_TRANSFER = 'transfer';

    const LEVEL_UNDERGRADUATE = 'undergraduate';

    const LEVEL_GRADUATE = 'graduate';

    const LEVEL_DOCTORAL = 'doctoral';

    const LEVEL_CERTIFICATE = 'certificate';

    const LEVEL_CONTINUING_EDUCATION = 'continuing_education';

    const GENDER_MALE = 'male';

    const GENDER_FEMALE = 'female';

    const GENDER_OTHER = 'other';

    const GENDER_PREFER_NOT_SAY = 'prefer_not_to_say';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advisor()
    {
        return $this->belongsTo(Faculty::class, 'advisor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function transcripts()
    {
        return $this->hasMany(Transcript::class);
    }

    public function financialAid()
    {
        return $this->hasMany(FinancialAid::class);
    }

    public function disciplinaryRecords()
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // Encrypt SSN when setting
    public function setSsnEncryptedAttribute($value)
    {
        $this->attributes['ssn_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    // Decrypt SSN when getting
    public function getSsnAttribute()
    {
        return $this->ssn_encrypted ? Crypt::decryptString($this->ssn_encrypted) : null;
    }

    public function getFullNameAttribute()
    {
        $name = trim($this->first_name.' '.($this->middle_name ? $this->middle_name.' ' : '').$this->last_name);

        return $name;
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', '.$this->address_line_2;
        }
        $address .= ', '.$this->city.', '.$this->state.' '.$this->postal_code;

        return $address;
    }

    public function getCurrentSemesterEnrollmentsAttribute()
    {
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return collect();
        }

        return $this->enrollments()
            ->where('semester_id', $currentSemester->id)
            ->with('course')
            ->get();
    }

    public function getCompletionRateAttribute()
    {
        if ($this->credit_hours_attempted == 0) {
            return 100;
        }

        return round(($this->credit_hours_completed / $this->credit_hours_attempted) * 100, 2);
    }

    public function isActive()
    {
        return $this->student_status === self::STATUS_ACTIVE;
    }

    public function isGraduated()
    {
        return $this->student_status === self::STATUS_GRADUATED;
    }

    public function calculateGPA()
    {
        $gradePoints = 0;
        $totalCredits = 0;

        foreach ($this->grades as $grade) {
            if ($grade->affects_gpa && $grade->grade_points !== null) {
                $gradePoints += $grade->grade_points * $grade->credit_hours;
                $totalCredits += $grade->credit_hours;
            }
        }

        return $totalCredits > 0 ? round($gradePoints / $totalCredits, 3) : 0.000;
    }

    public function scopeActive($query)
    {
        return $query->where('student_status', self::STATUS_ACTIVE);
    }

    public function scopeByAcademicLevel($query, $level)
    {
        return $query->where('academic_level', $level);
    }

    public function scopeByMajor($query, $major)
    {
        return $query->where('major', $major);
    }

    public function scopeGraduatingThisSemester($query)
    {
        $currentSemester = AcademicSemester::current();
        if (! $currentSemester) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('expected_graduation', '<=', $currentSemester->end_date)
            ->where('student_status', self::STATUS_ACTIVE);
    }
}
