<?php

namespace Aero\Dms\Models;

use Aero\HRM\Models\HrDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(DocumentCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(DocumentCategory::class, 'parent_id');
    }

    /**
     * Get the documents in this category.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(HrDocument::class, 'category_id');
    }
}
