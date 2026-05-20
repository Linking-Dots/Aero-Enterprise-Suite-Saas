<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanModule extends CentralModel
{
    protected $table = 'plan_modules';

    protected $fillable = [
        'plan_id',
        'module_code',
        'is_enabled',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
