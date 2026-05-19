<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmSafetyTraining extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_trainings';

    protected $fillable = ['title', 'description', 'type', 'duration_minutes', 'mandatory', 'active'];

    protected function casts(): array
    {
        return [
            'mandatory' => 'boolean',
            'active' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HrmSafetyTrainingAssignment::class, 'training_id');
    }
}
