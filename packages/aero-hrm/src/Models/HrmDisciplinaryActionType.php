<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmDisciplinaryActionType extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_disciplinary_action_types';

    protected $fillable = [
        'name', 'severity', 'description', 'escalates_after_count', 'escalates_to_type', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'escalates_after_count' => 'integer'];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(HrmDisciplinaryCase::class, 'action_type_id');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(HrmWarning::class, 'action_type_id');
    }
}
