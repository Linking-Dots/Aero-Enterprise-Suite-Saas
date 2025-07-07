<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
        'parent_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the trainings in this category.
     */
    public function trainings()
    {
        return $this->hasMany(Training::class, 'category_id');
    }

    /**
     * Get the user who created the category.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(TrainingCategory::class, 'parent_id');
    }

    /**
     * Get the subcategories of this category.
     */
    public function subcategories()
    {
        return $this->hasMany(TrainingCategory::class, 'parent_id');
    }
}
