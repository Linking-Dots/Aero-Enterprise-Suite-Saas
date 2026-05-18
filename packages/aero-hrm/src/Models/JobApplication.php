<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Job Application Model
 *
 * Represents a candidate's application for a job posting.
 *
 * @property int $id
 * @property int $job_id
 * @property int|null $current_stage_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $address
 * @property string|null $city
 * @property string|null $country
 * @property string|null $resume_path
 * @property string|null $cover_letter_path
 * @property string|null $cover_letter_text
 * @property string|null $portfolio_url
 * @property string|null $linkedin_url
 * @property float|null $expected_salary
 * @property int $years_of_experience
 * @property Carbon|null $available_from
 * @property string $status
 * @property int|null $overall_score
 * @property int|null $assigned_to
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $candidate_name
 * @property-read string $candidate_email
 * @property-read int $job_posting_id Alias for job_id
 * @property-read Job|null $job
 * @property-read JobHiringStage|null $currentStage
 * @property-read User|null $assignedTo
 */
class JobApplication extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_applications';

    protected $fillable = [
        'job_id',
        'current_stage_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'city',
        'country',
        'resume_path',
        'cover_letter_path',
        'cover_letter_text',
        'portfolio_url',
        'linkedin_url',
        'expected_salary',
        'years_of_experience',
        'available_from',
        'status',
        'overall_score',
        'assigned_to',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'available_from' => 'date',
        'expected_salary' => EncryptedField::class,
        'phone' => EncryptedField::class,
        'address' => EncryptedField::class,
        'years_of_experience' => 'integer',
        'overall_score' => 'integer',
    ];

    /**
     * Get the full name of the candidate.
     */
    public function getCandidateNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Alias for email to match event expectations.
     */
    public function getCandidateEmailAttribute(): string
    {
        return $this->email;
    }

    /**
     * Alias for job_id to match event expectations.
     */
    public function getJobPostingIdAttribute(): int
    {
        return $this->job_id;
    }

    /**
     * Get the job posting this application is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * Get the current hiring stage.
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(JobHiringStage::class, 'current_stage_id');
    }

    /**
     * Get the user this application is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the stage history for this application.
     */
    public function stageHistory(): HasMany
    {
        return $this->hasMany(JobApplicationStageHistory::class, 'application_id');
    }

    /**
     * Get status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'applied' => 'primary',
            'screening' => 'secondary',
            'shortlisted' => 'success',
            'interview' => 'warning',
            'offered' => 'success',
            'hired' => 'success',
            'rejected' => 'danger',
            'withdrawn' => 'default',
            default => 'default',
        };
    }
}
