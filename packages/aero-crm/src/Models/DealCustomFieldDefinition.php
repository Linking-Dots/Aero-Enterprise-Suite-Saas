<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;

class DealCustomFieldDefinition extends TenantModel
{
    protected $fillable = [
        'pipeline_id',
        'name',
        'key',
        'field_type',
        'options',
        'default_value',
        'is_required',
        'display_order',
        'show_in_list',
        'show_in_create',
        'show_in_edit',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'display_order' => 'integer',
        'show_in_list' => 'boolean',
        'show_in_create' => 'boolean',
        'show_in_edit' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Field types
     */
    const TYPE_TEXT = 'text';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_NUMBER = 'number';

    const TYPE_CURRENCY = 'currency';

    const TYPE_DATE = 'date';

    const TYPE_DATETIME = 'datetime';

    const TYPE_SELECT = 'select';

    const TYPE_MULTI_SELECT = 'multi_select';

    const TYPE_CHECKBOX = 'checkbox';

    const TYPE_URL = 'url';

    const TYPE_EMAIL = 'email';

    const TYPE_PHONE = 'phone';

    const TYPE_USER = 'user';

    const TYPE_FILE = 'file';

    /**
     * Get the pipeline
     */
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Scope for active fields
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope for fields shown in list view
     */
    public function scopeShowInList($query)
    {
        return $query->where('show_in_list', true);
    }

    /**
     * Scope for fields shown in create form
     */
    public function scopeShowInCreate($query)
    {
        return $query->where('show_in_create', true);
    }

    /**
     * Scope for fields shown in edit form
     */
    public function scopeShowInEdit($query)
    {
        return $query->where('show_in_edit', true);
    }

    /**
     * Check if field requires options (select types)
     */
    public function requiresOptions(): bool
    {
        return in_array($this->field_type, [self::TYPE_SELECT, self::TYPE_MULTI_SELECT]);
    }
}
