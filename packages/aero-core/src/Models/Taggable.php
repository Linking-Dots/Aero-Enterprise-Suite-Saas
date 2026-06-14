<?php

declare(strict_types=1);

namespace Aero\Core\Models;

/**
 * Pivot model for the polymorphic `taggables` table (tag_id, taggable_type,
 * taggable_id, tenant_id). Backs Tag::records() so it can be counted/queried without
 * a morphedByMany to the abstract base Model (which is not instantiable).
 */
class Taggable extends TenantModel
{
    protected $table = 'taggables';

    public $timestamps = false;

    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
        'tenant_id',
        'created_by',
    ];
}
