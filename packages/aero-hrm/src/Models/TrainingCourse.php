<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\TrainingCourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCourse extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_courses';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'description',
        'delivery_mode',
        'duration_minutes',
        'learning_objectives',
        'prerequisites',
        'tags',
        'is_mandatory',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
            'prerequisites' => 'array',
            'tags' => 'array',
            'is_mandatory' => 'bool',
            'is_active' => 'bool',
        ];
    }

    protected static function newFactory(): TrainingCourseFactory
    {
        return TrainingCourseFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(TrainingCourseMaterial::class, 'course_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'course_id');
    }
}
