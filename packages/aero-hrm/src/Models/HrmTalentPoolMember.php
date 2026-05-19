<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmTalentPoolMember extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_talent_pool_members';

    const TIER_HIGH_POTENTIAL = 'high_potential';

    const TIER_KEY_TALENT = 'key_talent';

    const TIER_EMERGING = 'emerging';

    protected $fillable = [
        'employee_id',
        'tier',
        'notes',
        'added_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
