<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCheckIn extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'mood',
        'note',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_anonymous' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
