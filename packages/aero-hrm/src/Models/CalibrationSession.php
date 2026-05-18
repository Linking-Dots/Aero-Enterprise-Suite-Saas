<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\CalibrationSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationSession extends TenantModel
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'hrm_calibration_sessions';

    /** @var list<string> */
    protected $fillable = [
        'cycle_id',
        'name',
        'grid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'grid' => 'array',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class, 'cycle_id');
    }

    protected static function newFactory(): CalibrationSessionFactory
    {
        return CalibrationSessionFactory::new();
    }
}
