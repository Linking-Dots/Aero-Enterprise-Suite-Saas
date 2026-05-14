<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyIncidentParticipant extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'employee_id',
        'involvement_type', // involved, injured, witness
        'injury_details',
        'treatment_received',
        'medical_attention_required',
        'time_off_work_required',
        'time_off_start_date',
        'time_off_end_date',
        'notes',
    ];

    protected $casts = [
        'medical_attention_required' => 'boolean',
        'time_off_work_required' => 'boolean',
        'time_off_start_date' => 'date',
        'time_off_end_date' => 'date',
    ];

    /**
     * Get the incident that this participant is involved in.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class, 'incident_id');
    }

    /**
     * Get the employee who is the participant.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
