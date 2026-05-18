<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCourseMaterial extends TenantModel
{
    protected $table = 'training_course_materials';

    protected $fillable = [
        'course_id',
        'kind',
        'label',
        'url',
        'file_path',
        'display_order',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }
}
