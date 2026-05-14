<?php

namespace Aero\CustomFields\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Aero\Core\Models\User;

class CustomFieldValue extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    // Relationships
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    // Accessors
    public function getTypedValueAttribute()
    {
        $field = $this->customField;
        
        if (!$field) {
            return $this->value;
        }

        return match ($field->field_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number', 'currency' => is_numeric($this->value) ? (float) $this->value : $this->value,
            'select', 'multi_select' => json_decode($this->value, true) ?? $this->value,
            'date' => $this->value ? \Carbon\Carbon::parse($this->value)->toDateString() : null,
            'datetime' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            default => $this->value,
        };
    }
}
