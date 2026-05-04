<?php

namespace Aero\Core\Services;

use Aero\Core\Models\CustomField;
use Aero\Core\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomFieldService
{
    /**
     * Get custom fields for a specific entity type.
     */
    public function getFieldsForEntity(string $entityType, bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = CustomField::forEntity($entityType);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get custom field values for a specific entity.
     */
    public function getValuesForEntity(string $entityType, int $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomFieldValue::forEntity($entityType, $entityId)
            ->with('customField')
            ->get();
    }

    /**
     * Get all custom fields with their values for an entity.
     */
    public function getFieldsWithValues(string $entityType, int $entityId): array
    {
        $fields = $this->getFieldsForEntity($entityType);
        $values = $this->getValuesForEntity($entityType, $entityId);

        $valuesMap = $values->keyBy('custom_field_id');

        return $fields->map(function ($field) use ($valuesMap) {
            $value = $valuesMap->get($field->id);
            return [
                'field' => $field,
                'value' => $value ? $value->typed_value : null,
                'value_id' => $value ? $value->id : null,
            ];
        })->keyBy('field.id')->all();
    }

    /**
     * Create a new custom field.
     */
    public function createField(array $data): CustomField
    {
        return DB::transaction(function () use ($data) {
            $field = CustomField::create([
                'name' => $data['name'],
                'code' => $this->generateCode($data['name'], $data['entity_type']),
                'entity_type' => $data['entity_type'],
                'field_type' => $data['field_type'],
                'options' => $data['options'] ?? null,
                'validation_rules' => $data['validation_rules'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'is_unique' => $data['is_unique'] ?? false,
                'is_searchable' => $data['is_searchable'] ?? true,
                'is_filterable' => $data['is_filterable'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'placeholder' => $data['placeholder'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            Log::info('Custom field created', [
                'field_id' => $field->id,
                'code' => $field->code,
                'entity_type' => $field->entity_type,
            ]);

            return $field;
        });
    }

    /**
     * Update a custom field.
     */
    public function updateField(int $fieldId, array $data): CustomField
    {
        return DB::transaction(function () use ($fieldId, $data) {
            $field = CustomField::findOrFail($fieldId);

            $field->update([
                'name' => $data['name'] ?? $field->name,
                'entity_type' => $data['entity_type'] ?? $field->entity_type,
                'field_type' => $data['field_type'] ?? $field->field_type,
                'options' => $data['options'] ?? $field->options,
                'validation_rules' => $data['validation_rules'] ?? $field->validation_rules,
                'is_required' => $data['is_required'] ?? $field->is_required,
                'is_unique' => $data['is_unique'] ?? $field->is_unique,
                'is_searchable' => $data['is_searchable'] ?? $field->is_searchable,
                'is_filterable' => $data['is_filterable'] ?? $field->is_filterable,
                'sort_order' => $data['sort_order'] ?? $field->sort_order,
                'placeholder' => $data['placeholder'] ?? $field->placeholder,
                'description' => $data['description'] ?? $field->description,
                'is_active' => $data['is_active'] ?? $field->is_active,
                'updated_by' => auth()->id(),
            ]);

            Log::info('Custom field updated', [
                'field_id' => $field->id,
                'code' => $field->code,
            ]);

            return $field->fresh();
        });
    }

    /**
     * Delete a custom field.
     */
    public function deleteField(int $fieldId): bool
    {
        return DB::transaction(function () use ($fieldId) {
            $field = CustomField::findOrFail($fieldId);
            
            // Delete all associated values
            $field->values()->delete();
            
            // Delete the field (soft delete)
            $field->delete();

            Log::info('Custom field deleted', [
                'field_id' => $field->id,
                'code' => $field->code,
            ]);

            return true;
        });
    }

    /**
     * Save custom field values for an entity.
     */
    public function saveValues(string $entityType, int $entityId, array $values): bool
    {
        return DB::transaction(function () use ($entityType, $entityId, $values) {
            foreach ($values as $fieldId => $value) {
                $field = CustomField::findOrFail($fieldId);

                // Convert value to string for storage
                $stringValue = $this->convertValueToString($value, $field->field_type);

                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $fieldId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ],
                    [
                        'value' => $stringValue,
                        'updated_by' => auth()->id(),
                    ]
                );
            }

            Log::info('Custom field values saved', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'count' => count($values),
            ]);

            return true;
        });
    }

    /**
     * Delete custom field values for an entity.
     */
    public function deleteValues(string $entityType, int $entityId, array $fieldIds = null): bool
    {
        $query = CustomFieldValue::forEntity($entityType, $entityId);

        if ($fieldIds) {
            $query->whereIn('custom_field_id', $fieldIds);
        }

        $deleted = $query->delete();

        Log::info('Custom field values deleted', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'count' => $deleted,
        ]);

        return true;
    }

    /**
     * Generate a unique code from field name.
     */
    protected function generateCode(string $name, string $entityType): string
    {
        $baseCode = strtolower(str_replace([' ', '-', '_'], '_', $name));
        $code = "{$entityType}_{$baseCode}";
        
        $counter = 1;
        $originalCode = $code;
        
        while (CustomField::where('code', $code)->exists()) {
            $code = "{$originalCode}_{$counter}";
            $counter++;
        }

        return $code;
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
     * Validate custom field values against field rules.
     */
    public function validateValues(string $entityType, array $values): array
    {
        $errors = [];
        $fields = $this->getFieldsForEntity($entityType);

        foreach ($fields as $field) {
            $value = $values[$field->id] ?? null;

            // Check required fields
            if ($field->is_required && empty($value)) {
                $errors[$field->code] = "{$field->name} is required.";
                continue;
            }

            // Check uniqueness if applicable
            if ($field->is_unique && !empty($value)) {
                $exists = CustomFieldValue::where('custom_field_id', $field->id)
                    ->where('value', $this->convertValueToString($value, $field->field_type))
                    ->exists();

                if ($exists) {
                    $errors[$field->code] = "{$field->name} must be unique.";
                }
            }

            // Additional validation based on field type
            if (!empty($value)) {
                $fieldErrors = $this->validateFieldType($value, $field);
                if (!empty($fieldErrors)) {
                    $errors[$field->code] = $fieldErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate value against field type.
     */
    protected function validateFieldType($value, CustomField $field): ?string
    {
        return match ($field->field_type) {
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) ? 'Invalid email format.' : null,
            'number', 'currency' => !is_numeric($value) ? 'Must be a number.' : null,
            'date' => !strtotime($value) ? 'Invalid date format.' : null,
            default => null,
        };
    }
}
