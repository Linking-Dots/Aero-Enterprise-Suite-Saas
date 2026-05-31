<?php

namespace Aero\Core\Models;

class EmailTemplate extends TenantModel
{
    protected $table = 'email_templates';

    protected $fillable = [
        'name', 'slug', 'subject', 'body_html', 'body_text',
        'category', 'variables', 'is_active', 'is_locked',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
