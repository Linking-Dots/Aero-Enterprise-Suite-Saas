<?php

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectTypeMetadata
 *
 * Stores domain-specific field values for projects based on their type.
 * This allows each project type to have different custom fields without
 * adding columns to the main projects table.
 *
 * ARCHITECTURAL PRINCIPLE:
 * - Field definitions come from config/project_types.php
 * - Values stored as key-value pairs with type information
 * - Supports text, number, date, boolean, json field types
 */
class ProjectTypeMetadata extends TenantModel
{
    protected $table = 'project_type_metadata';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'field_code',
        'field_type',
        'field_value',
        'field_options',
    ];

    protected $casts = [
        'field_options' => 'array',
    ];

    /**
     * Supported field types.
     */
    public const FIELD_TYPES = [
        'text' => 'Text',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'integer' => 'Integer',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'boolean' => 'Yes/No',
        'select' => 'Dropdown',
        'tags' => 'Tags',
        'url' => 'URL',
        'email' => 'Email',
        'address' => 'Address',
        'user' => 'User Reference',
        'departments' => 'Departments',
        'json' => 'JSON Data',
    ];

    /**
     * Parent project relationship.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the casted value based on field type.
     */
    public function getCastedValueAttribute(): mixed
    {
        return match ($this->field_type) {
            'integer' => (int) $this->field_value,
            'decimal', 'number' => (float) $this->field_value,
            'boolean' => filter_var($this->field_value, FILTER_VALIDATE_BOOLEAN),
            'date' => $this->field_value ? \Carbon\Carbon::parse($this->field_value)->toDateString() : null,
            'datetime' => $this->field_value ? \Carbon\Carbon::parse($this->field_value) : null,
            'json', 'tags', 'departments' => json_decode($this->field_value, true),
            default => $this->field_value,
        };
    }

    /**
     * Set value with proper serialization based on type.
     */
    public function setValueAttribute(mixed $value): void
    {
        $this->field_value = match ($this->field_type) {
            'json', 'tags', 'departments' => is_array($value) ? json_encode($value) : $value,
            'boolean' => $value ? '1' : '0',
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value,
            default => (string) $value,
        };
    }
}
