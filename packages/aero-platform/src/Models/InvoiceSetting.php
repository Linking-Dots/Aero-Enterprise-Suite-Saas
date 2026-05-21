<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceSetting extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'prefix',
        'next_number',
        'digit_padding',
        'active_template_id',
        'logo_path',
        'company_name',
        'footer_text',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'digit_padding' => 'integer',
    ];

    public function activeTemplate(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class, 'active_template_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
