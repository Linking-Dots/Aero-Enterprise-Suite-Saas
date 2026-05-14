<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCustomField extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'field_name',
        'field_label',
        'field_type',
        'field_options',
        'is_required',
        'placeholder',
        'help_text',
        'display_order',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
