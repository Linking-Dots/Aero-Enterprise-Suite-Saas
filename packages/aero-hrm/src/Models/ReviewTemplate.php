<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\ReviewTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewTemplate extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_review_templates';

    protected $fillable = [
        'name',
        'sections',
        'rating_scale',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'rating_scale' => 'array',
            'active' => 'bool',
        ];
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(ReviewCycle::class, 'template_id');
    }

    protected static function newFactory(): ReviewTemplateFactory
    {
        return ReviewTemplateFactory::new();
    }
}
