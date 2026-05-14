<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\Position;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competency extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'level', // entry, mid, senior, expert
        'status', // active, inactive
    ];

    /**
     * Get the skills associated with this competency.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'competency_skills');
    }

    /**
     * Get positions requiring this competency.
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'position_competencies')
            ->withPivot('importance', 'notes')
            ->withTimestamps();
    }
}
