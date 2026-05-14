<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Job Offer Model
 *
 * @property int $id
 * @property int $application_id
 * @property string|null $offer_letter_path
 * @property float $offered_salary
 * @property \Carbon\Carbon $joining_date
 * @property \Carbon\Carbon $offer_valid_until
 * @property string|null $terms_and_conditions
 * @property string|null $benefits
 * @property string $status
 * @property int|null $sent_by
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $responded_at
 * @property string|null $candidate_response_notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read JobApplication $application
 * @property-read User|null $sentBy
 */
class JobOffer extends TenantModel
{
    use HasFactory;

    protected $table = 'job_offers';

    protected $fillable = [
        'application_id',
        'offer_letter_path',
        'offered_salary',
        'joining_date',
        'offer_valid_until',
        'terms_and_conditions',
        'benefits',
        'status',
        'sent_by',
        'sent_at',
        'responded_at',
        'candidate_response_notes',
    ];

    protected $casts = [
        'offered_salary' => 'decimal:2',
        'joining_date' => 'date',
        'offer_valid_until' => 'date',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the job application this offer is for.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    /**
     * Get the user who sent the offer.
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'default',
            'sent' => 'primary',
            'accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'secondary',
            default => 'default',
        };
    }
}
