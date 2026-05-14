<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataTransformation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_data_transformations';

    protected $fillable = [
        'name', 'data_sync_job_id', 'transformation_type', 'source_field',
        'target_field', 'transformation_rules', 'is_required', 'default_value',
        'validation_rules', 'is_active', 'created_by',
    ];

    protected $casts = [
        'data_sync_job_id' => 'integer',
        'transformation_rules' => 'json',
        'is_required' => 'boolean',
        'validation_rules' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_DIRECT_MAPPING = 'direct_mapping';

    const TYPE_VALUE_MAPPING = 'value_mapping';

    const TYPE_FUNCTION = 'function';

    const TYPE_CONCATENATION = 'concatenation';

    const TYPE_CALCULATION = 'calculation';

    const TYPE_LOOKUP = 'lookup';

    const TYPE_CONDITIONAL = 'conditional';

    public function dataSyncJob()
    {
        return $this->belongsTo(DataSyncJob::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transformValue($sourceValue)
    {
        switch ($this->transformation_type) {
            case self::TYPE_DIRECT_MAPPING:
                return $sourceValue;

            case self::TYPE_VALUE_MAPPING:
                return $this->transformation_rules['mappings'][$sourceValue] ?? $this->default_value;

            case self::TYPE_FUNCTION:
                return $this->applyFunction($sourceValue, $this->transformation_rules['function']);

            case self::TYPE_CONCATENATION:
                return $this->applyConcatenation($sourceValue, $this->transformation_rules);

            default:
                return $sourceValue;
        }
    }

    private function applyFunction($value, $function)
    {
        switch ($function) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            default:
                return $value;
        }
    }

    private function applyConcatenation($value, $rules)
    {
        $parts = [];
        foreach ($rules['parts'] as $part) {
            if ($part['type'] === 'field') {
                $parts[] = $value;
            } elseif ($part['type'] === 'literal') {
                $parts[] = $part['value'];
            }
        }

        return implode($rules['separator'] ?? '', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
