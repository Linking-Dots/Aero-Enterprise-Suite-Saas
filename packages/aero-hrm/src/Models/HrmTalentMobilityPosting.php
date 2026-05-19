<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmTalentMobilityPosting extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_talent_mobility_postings';

    const STATUS_OPEN = 'open';

    const STATUS_CLOSED = 'closed';

    const TYPE_TRANSFER = 'transfer';

    const TYPE_PROJECT = 'project';

    const TYPE_SECONDMENT = 'secondment';

    const TYPE_PROMOTION = 'promotion';

    protected $fillable = [
        'title',
        'description',
        'type',
        'department_id',
        'role_id',
        'closes_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'closes_at' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'role_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
