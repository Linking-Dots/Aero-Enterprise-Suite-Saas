<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StatusPageComponent extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'status_page_components';

    protected $fillable = [
        'name',
        'description',
        'status',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
