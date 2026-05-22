<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class KbArticle extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'body_md', 'category', 'tags',
        'is_published', 'published_at', 'view_count', 'author_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
    ];
}
