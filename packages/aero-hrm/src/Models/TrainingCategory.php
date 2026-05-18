<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\TrainingCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
        ];
    }

    protected static function newFactory(): TrainingCategoryFactory
    {
        return TrainingCategoryFactory::new();
    }

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class, 'category_id');
    }
}
