<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Safety Incident Model
 *
 * @property int $id
 * @property \Carbon\Carbon $incident_date
 * @property \Carbon\Carbon|null $incident_time
 * @property string|null $location
 * @property string $incident_type
 * @property string $severity
 * @property int $reported_by User ID who reported the incident
 * @property string|null $description
 * @property string|null $immediate_actions
 * @property string|null $root_cause
 * @property string|null $corrective_actions
 * @property string $status
 * @property array|null $witnesses
 * @property bool $reported_to_authorities
 * @property \Carbon\Carbon|null $authority_report_date
 * @property array|null $related_documents
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User $reporter
 */
class SafetyIncident extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'incident_date',
        'incident_time',
        'location',
        'incident_type', // injury, near-miss, property-damage, environmental, other
        'severity', // minor, moderate, serious, critical
        'reported_by',
        'description',
        'immediate_actions',
        'root_cause',
        'corrective_actions',
        'status', // reported, investigating, resolved, closed
        'witnesses',
        'reported_to_authorities',
        'authority_report_date',
        'related_documents',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'incident_time' => 'datetime',
        'authority_report_date' => 'date',
        'reported_to_authorities' => 'boolean',
        'witnesses' => 'array',
        'related_documents' => 'array',
    ];

    /**
     * Get the user who reported the incident.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the incident participants.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(SafetyIncidentParticipant::class, 'incident_id');
    }
}
