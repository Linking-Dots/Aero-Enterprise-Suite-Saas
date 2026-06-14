<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Kernel\Encryption\EncryptedField;
use Aero\HRM\Database\Factories\EmployeeFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Employee Model
 *
 * Contains all employment-specific information separate from authentication.
 * This is the central model for the Employee Information System (EIS).
 *
 * Architecture:
 * - User: Authentication/login data, user profile image (identity)
 * - Employee: Employment data, employee image (HR/work purposes: badges, org charts)
 *
 * Media Collections:
 * - employee_images: Official employee photo for HR purposes (badges, org charts)
 * - employee_documents: Employment-related documents
 *
 * Flow:
 * 1. User is created (invited or registered)
 * 2. User is onboarded as Employee (Employee record created)
 * 3. Employee profile data (addresses, education, bank) linked to Employee
 *
 * A User can exist without being an Employee (admin, external user)
 * An Employee MUST have a User account for authentication.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $department_id
 * @property int|null $designation_id
 * @property int|null $manager_id
 * @property string $employee_code
 * @property Carbon $date_of_joining
 * @property Carbon|null $date_of_leaving
 * @property Carbon|null $probation_end_date
 * @property Carbon|null $confirmation_date
 * @property string $employment_type
 * @property string $status
 * @property float $basic_salary
 * @property string|null $work_location
 * @property string|null $shift
 * @property string|null $notes
 *
 * Employment date extras:
 * @property Carbon|null $joining_date
 * @property int|null $probation_period_months
 * @property Carbon|null $contract_start_date
 * @property Carbon|null $contract_end_date
 *
 * Personal Info:
 * @property Carbon|null $birthday
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $nationality
 * @property string|null $religion
 * @property string|null $marital_status
 * @property string|null $blood_group
 *
 * Identity documents:
 * @property string|null $passport_no
 * @property Carbon|null $passport_expiry
 * @property string|null $visa_no
 * @property Carbon|null $visa_expiry
 * @property string|null $emirates_id
 * @property Carbon|null $emirates_id_expiry
 *
 * Accessors:
 * @property-read string $name
 * @property-read string $full_name
 * @property-read User $user
 * @property-read Department|null $department
 * @property-read Designation|null $designation
 * @property-read Employee|null $manager
 */
class Employee extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return EmployeeFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Core linkage
        'user_id',
        'department_id',
        'designation_id',
        'salary_structure_id',
        'manager_id',

        // ── Employee identifiers ───────────────────────────────────────────────
        'employee_code',

        // ── Employment dates ───────────────────────────────────────────────────
        'date_of_joining',
        'joining_date',           // alias kept for backward compat
        'date_of_leaving',
        'probation_end_date',
        'probation_period_months',
        'contract_start_date',
        'contract_end_date',
        'confirmation_date',

        // ── Employment details ─────────────────────────────────────────────────
        'employment_type',
        'status',
        'basic_salary',
        'work_location',
        'shift',

        // ── Personal / Demographic info ────────────────────────────────────────
        'birthday',
        'date_of_birth',
        'gender',
        'nationality',
        'religion',
        'marital_status',
        'blood_group',

        // ── Identity documents ─────────────────────────────────────────────────
        'passport_no',
        'passport_expiry',        // canonical column name in migration
        'visa_no',
        'visa_expiry',
        'emirates_id',
        'emirates_id_expiry',
        'national_id',

        // ── Banking ────────────────────────────────────────────────────────────
        'bank_account_number',

        // ── Notes ──────────────────────────────────────────────────────────────
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            // Employment dates
            'date_of_joining' => 'date',
            'joining_date' => 'date',
            'date_of_leaving' => 'date',
            'probation_end_date' => 'date',
            'probation_period_months' => 'integer',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'confirmation_date' => 'date',
            // Personal
            'birthday' => 'date',
            'date_of_birth' => 'date',
            // Documents
            'passport_expiry' => 'date',
            'visa_expiry' => 'date',
            'emirates_id_expiry' => 'date',
            // Salary
            'basic_salary' => 'decimal:2',
            // PII — encrypted at rest
            'passport_no' => EncryptedField::class,
            'visa_no' => EncryptedField::class,
            'emirates_id' => EncryptedField::class,
            'national_id' => EncryptedField::class,
            'bank_account_number' => EncryptedField::class,
        ];
    }

    /**
     * Employment type options.
     */
    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full Time',
        'part_time' => 'Part Time',
        'contract' => 'Contract',
        'intern' => 'Intern',
    ];

    /**
     * Status options.
     */
    public const STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'resigned' => 'Resigned',
        'terminated' => 'Terminated',
        'retired' => 'Retired',
    ];

    /**
     * Gender options.
     */
    public const GENDERS = [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
    ];

    /**
     * Marital status options.
     */
    public const MARITAL_STATUSES = [
        'single' => 'Single',
        'married' => 'Married',
        'divorced' => 'Divorced',
        'widowed' => 'Widowed',
    ];

    // =========================================================================
    // CORE RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user account associated with this employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'user_id');
    }

    /**
     * Get the department this employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the designation/position of this employee.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get the manager (another user) this employee reports to.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'manager_id');
    }

    /**
     * Get the manager's employee record.
     */
    public function managerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id', 'user_id');
    }

    /**
     * Get employees who report to this employee.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id', 'user_id');
    }

    // =========================================================================
    // HRM OPERATIONS (linked via user_id for backward compatibility)
    // =========================================================================

    /**
     * Get attendance records for this employee.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }

    /**
     * Get leave records for this employee.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'user_id', 'user_id');
    }

    /**
     * Get the legacy pivot salary-structure rows for this employee.
     */
    public function salaryStructure(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class, 'employee_id');
    }

    /**
     * Get the v2 SalaryStructure template directly assigned to this employee.
     * Used by PayrollRunGenerator to determine payroll computation template.
     */
    public function payrollStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    // =========================================================================
    // EMPLOYEE PROFILE RELATIONSHIPS
    // =========================================================================

    /**
     * Get the employee's bank details (1:1).
     */
    public function bankDetail(): HasOne
    {
        return $this->hasOne(EmployeeBankDetail::class, 'employee_id');
    }

    /**
     * Get the employee's personal documents (1:Many).
     */
    public function personalDocuments(): HasMany
    {
        return $this->hasMany(EmployeePersonalDocument::class, 'employee_id');
    }

    /**
     * Get the employee's emergency contacts (1:Many).
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class, 'employee_id')->orderBy('priority');
    }

    /**
     * Get the employee's primary emergency contact.
     */
    public function primaryEmergencyContact(): HasOne
    {
        return $this->hasOne(EmergencyContact::class, 'employee_id')->where('is_primary', true);
    }

    /**
     * Get the employee's addresses (1:Many).
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class, 'employee_id');
    }

    /**
     * Get the employee's current address.
     */
    public function currentAddress(): HasOne
    {
        return $this->hasOne(EmployeeAddress::class, 'employee_id')->where('address_type', 'current');
    }

    /**
     * Get the employee's permanent address.
     */
    public function permanentAddress(): HasOne
    {
        return $this->hasOne(EmployeeAddress::class, 'employee_id')->where('address_type', 'permanent');
    }

    /**
     * Get the employee's education records (1:Many).
     */
    public function education(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class, 'employee_id')->latest('end_date');
    }

    /**
     * Get the employee's work experience records (1:Many).
     */
    public function workExperience(): HasMany
    {
        return $this->hasMany(EmployeeWorkExperience::class, 'employee_id')->latest('end_date');
    }

    /**
     * Get the employee's certifications (1:Many).
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class, 'employee_id');
    }

    /**
     * Get the employee's valid certifications.
     */
    public function validCertifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class, 'employee_id')
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Get the employee's dependents (1:Many).
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class, 'employee_id');
    }

    /**
     * Get the employee's beneficiaries (dependents marked as beneficiary).
     */
    public function beneficiaries(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class, 'employee_id')->where('is_beneficiary', true);
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    /**
     * Get the full name from the associated user.
     */
    public function getNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }

    /**
     * Get the email from the associated user.
     */
    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? '';
    }

    /**
     * Get the phone from the associated user.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    /**
     * Get profile image URL from the associated user.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->user?->profile_image_url;
    }

    /**
     * Check if employee is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee is on probation.
     */
    public function isOnProbation(): bool
    {
        if (! $this->probation_end_date) {
            return false;
        }

        return $this->probation_end_date->isFuture();
    }

    /**
     * Check if employee is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->confirmation_date !== null;
    }

    /**
     * Get tenure in days.
     */
    public function getTenureDaysAttribute(): int
    {
        $endDate = $this->date_of_leaving ?? now();

        return $this->date_of_joining ? $this->date_of_joining->diffInDays($endDate) : 0;
    }

    /**
     * Get tenure in years and months (formatted).
     */
    public function getTenureFormattedAttribute(): string
    {
        if (! $this->date_of_joining) {
            return 'N/A';
        }

        $endDate = $this->date_of_leaving ?? now();
        $years = $this->date_of_joining->diffInYears($endDate);
        $months = $this->date_of_joining->copy()->addYears($years)->diffInMonths($endDate);

        if ($years > 0 && $months > 0) {
            return "{$years}y {$months}m";
        } elseif ($years > 0) {
            return "{$years} year(s)";
        } elseif ($months > 0) {
            return "{$months} month(s)";
        }

        return 'Less than a month';
    }

    /**
     * Get age from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Get formatted employment type.
     */
    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }

    /**
     * Get formatted status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get formatted gender.
     */
    public function getGenderLabelAttribute(): string
    {
        return self::GENDERS[$this->gender] ?? $this->gender ?? 'Not specified';
    }

    /**
     * Get formatted marital status.
     */
    public function getMaritalStatusLabelAttribute(): string
    {
        return self::MARITAL_STATUSES[$this->marital_status] ?? $this->marital_status ?? 'Not specified';
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to employees in a specific department.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to employees with a specific designation.
     */
    public function scopeWithDesignation($query, $designationId)
    {
        return $query->where('designation_id', $designationId);
    }

    /**
     * Scope to employees who joined in a date range.
     */
    public function scopeJoinedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_of_joining', [$startDate, $endDate]);
    }

    /**
     * Scope to employees on probation.
     */
    public function scopeOnProbation($query)
    {
        return $query->where('probation_end_date', '>', now());
    }

    /**
     * Scope to confirmed employees.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmation_date');
    }

    /**
     * Scope with full profile relations.
     */
    public function scopeWithFullProfile($query)
    {
        return $query->with([
            'user:id,name,email,phone,profile_image',
            'department:id,name',
            'designation:id,title',
            'manager:id,name',
            'addresses',
            'emergencyContacts',
            'bankDetail',
        ]);
    }

    /**
     * Scope with basic relations for list views.
     */
    public function scopeWithBasicRelations($query)
    {
        return $query->with([
            'user:id,name,email,phone',
            'department:id,name',
            'designation:id,title',
        ]);
    }

    /**
     * Apply dynamic filters for index/list queries.
     *
     * Supported keys: search, department_id, status, employment_type.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $term) {
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('employee_code', 'like', "%{$term}%")
                        ->orWhereHas('user', fn (Builder $u) => $u
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($filters['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($filters['employment_type'] ?? null, fn (Builder $q, $t) => $q->where('employment_type', $t));
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Generate a unique employee code.
     */
    public static function generateEmployeeCode(string $prefix = 'EMP'): string
    {
        $lastEmployee = static::withTrashed()
            ->where('employee_code', 'like', $prefix.'%')
            ->orderBy('employee_code', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad((string) $newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Onboard a user as an employee.
     *
     * @param  User  $user  The user to onboard
     * @param  array  $data  Employment data (department_id, designation_id, etc.)
     * @return Employee The created employee record
     */
    public static function onboardUser(User $user, array $data): Employee
    {
        $data['user_id'] = $user->id;
        $data['employee_code'] = $data['employee_code'] ?? static::generateEmployeeCode();
        $data['date_of_joining'] = $data['date_of_joining'] ?? now();
        $data['status'] = $data['status'] ?? 'active';
        $data['employment_type'] = $data['employment_type'] ?? 'full_time';

        return static::create($data);
    }

    // =========================================================================
    // MEDIA LIBRARY
    // =========================================================================

    /**
     * Register media collections for the Employee model.
     *
     * Media Collections:
     * - employee_images: Official employee photo for HR purposes (badges, org charts, ID cards)
     * - employee_documents: Employment-related documents (contracts, certificates)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('employee_images')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useFallbackUrl('/images/default-employee-avatar.png');

        $this->addMediaCollection('employee_documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    /**
     * Register media conversions for optimized image sizes.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->sharpen(10)
            ->performOnCollections('employee_images');

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->sharpen(5)
            ->performOnCollections('employee_images');

        $this->addMediaConversion('large')
            ->width(600)
            ->height(600)
            ->performOnCollections('employee_images');
    }

    /**
     * Get the employee's image URL.
     *
     * This is the official HR photo used for badges, org charts, etc.
     * Different from User's profile_image which is for authentication/identity.
     *
     * @param  string  $conversion  The image conversion size (thumb, medium, large, or empty for original)
     * @return string|null The image URL or null if no image
     */
    public function getEmployeeImageUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('employee_images');

        if (! $media) {
            return null;
        }

        return $conversion ? $media->getUrl($conversion) : $media->getUrl();
    }

    /**
     * Get the employee's image URL with fallback to user's profile image.
     *
     * Falls back to the linked User's profile image if no employee image exists.
     *
     * @param  string  $conversion  The image conversion size
     * @return string The image URL (employee image, user profile image, or default avatar)
     */
    public function getEmployeeImageUrlWithFallback(string $conversion = ''): string
    {
        $employeeImage = $this->getEmployeeImageUrl($conversion);
        if ($employeeImage) {
            return $employeeImage;
        }

        return '/images/default-employee-avatar.png';
    }

    /**
     * Check if the employee has an image.
     */
    public function hasEmployeeImage(): bool
    {
        return $this->hasMedia('employee_images');
    }

    /**
     * Get the employee image URL attribute for easy access.
     *
     * Usage: $employee->employee_image_url
     */
    public function getEmployeeImageUrlAttribute(): ?string
    {
        return $this->getEmployeeImageUrl();
    }

    /**
     * Resolve the user model class without importing cross-package models.
     */
    protected function getUserModel(): string
    {
        return config('hrm.user_model', config('auth.providers.users.model'));
    }
}
