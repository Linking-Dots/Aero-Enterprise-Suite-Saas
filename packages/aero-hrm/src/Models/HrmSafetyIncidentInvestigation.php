<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmSafetyIncidentInvestigation extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_incident_investigations';

    protected $fillable = ['incident_id', 'investigator_id', 'root_cause', 'corrective_action'];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(HrmSafetyIncident::class, 'incident_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }
}
