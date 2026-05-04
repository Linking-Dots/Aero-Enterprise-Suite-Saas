<?php

namespace Aero\Core\Traits;

use Aero\Core\Models\CustomField;
use Aero\Core\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

trait HasCustomFields
{
    /**
     * Get the custom field values for this model.
     */
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    /**
     * Get custom fields with values for this model.
     */
    public function customFields(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomField::forEntity($this->getMorphClass())
            ->active()
            ->ordered()
            ->get()
            ->map(function ($field) {
                $value = $this->customFieldValues()
                    ->where('custom_field_id', $field->id)
                    ->first();
                
                return [
                    'field' => $field,
                    'value' => $value ? $value->typed_value : null,
                    'value_id' => $value ? $value->id : null,
                ];
            });
    }

    /**
     * Get a specific custom field value.
     */
    public function getCustomFieldValue(string $fieldCode)
    {
        $field = CustomField::where('code', $fieldCode)
            ->forEntity($this->getMorphClass())
            ->active()
            ->first();

        if (!$field) {
            return null;
        }

        $value = $this->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->first();

        return $value ? $value->typed_value : null;
    }

    /**
     * Set a custom field value.
     */
    public function setCustomFieldValue(string $fieldCode, $value): bool
    {
        $field = CustomField::where('code', $fieldCode)
            ->forEntity($this->getMorphClass())
            ->active()
            ->first();

        if (!$field) {
            return false;
        }

        $stringValue = $this->convertValueToString($value, $field->field_type);

        CustomFieldValue::updateOrCreate(
            [
                'custom_field_id' => $field->id,
                'entity_type' => $this->getMorphClass(),
                'entity_id' => $this->id,
            ],
            [
                'value' => $stringValue,
                'updated_by' => auth()->id(),
            ]
        );

        return true;
    }

    /**
     * Set multiple custom field values.
     */
    public function setCustomFieldValues(array $values): bool
    {
        foreach ($values as $fieldCode => $value) {
            $this->setCustomFieldValue($fieldCode, $value);
        }

        return true;
    }

    /**
     * Delete a custom field value.
     */
    public function deleteCustomFieldValue(string $fieldCode): bool
    {
        $field = CustomField::where('code', $fieldCode)
            ->forEntity($this->getMorphClass())
            ->first();

        if (!$field) {
            return false;
        }

        return $this->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->delete() > 0;
    }

    /**
     * Delete all custom field values for this model.
     */
    public function deleteAllCustomFieldValues(): bool
    {
        return $this->customFieldValues()->delete() > 0;
    }

    /**
     * Scope to filter by custom field value.
     */
    public function scopeWhereCustomField(Builder $query, string $fieldCode, $value): Builder
    {
        $field = CustomField::where('code', $fieldCode)
            ->forEntity($this->getMorphClass())
            ->active()
            ->first();

        if (!$field) {
            return $query;
        }

        return $query->whereHas('customFieldValues', function ($q) use ($field, $value) {
            $stringValue = $this->convertValueToString($value, $field->field_type);
            $q->where('custom_field_id', $field->id)
              ->where('value', $stringValue);
        });
    }

    /**
     * Convert value to string for storage.
     */
    protected function convertValueToString($value, string $fieldType): string
    {
        if ($value === null) {
            return '';
        }

        return match ($fieldType) {
            'boolean' => $value ? '1' : '0',
            'select', 'multi_select' => is_array($value) ? json_encode($value) : $value,
            default => (string) $value,
        };
    }

    /**
     * Get custom fields as an associative array.
     */
    public function getCustomFieldsArray(): array
    {
        return $this->customFields()->pluck('value', 'field.code')->toArray();
    }

    /**
     * Load custom fields with values.
     */
    public function loadCustomFields(): self
    {
        $this->load(['customFieldValues.customField']);
        return $this;
    }

    /**
     * Append custom fields to model's array output.
     */
    protected function initializeHasCustomFields()
    {
        $this->append('custom_fields_array');
    }

    /**
     * Get custom fields as array attribute.
     */
    public function getCustomFieldsArrayAttribute(): array
    {
        return $this->getCustomFieldsArray();
    }
}
