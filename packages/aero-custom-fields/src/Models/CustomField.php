<?php

namespace Aero\CustomFields\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Aero\Core\Models\User;

class CustomField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'entity_type',
        'field_type',
        'options',
        'validation_rules',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_filterable',
        'sort_order',
        'placeholder',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getFieldTypeLabelAttribute(): string
    {
        return match ($this->field_type) {
            'text' => 'Text',
            'number' => 'Number',
            'email' => 'Email',
            'date' => 'Date',
            'datetime' => 'Date Time',
            'boolean' => 'Yes/No',
            'select' => 'Dropdown',
            'multi_select' => 'Multi Select',
            'textarea' => 'Text Area',
            'file' => 'File Upload',
            'currency' => 'Currency',
            default => ucfirst($this->field_type),
        };
    }

    public function getValidationRulesArrayAttribute(): array
    {
        return $this->validation_rules ?? [];
    }
}
