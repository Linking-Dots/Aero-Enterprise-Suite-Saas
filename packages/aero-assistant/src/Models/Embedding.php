<?php

declare(strict_types=1);

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;

class Embedding extends TenantModel
{
    protected $table = 'aeon_embeddings';

    protected $fillable = ['source_type', 'source_ref', 'title', 'chunk_text', 'vector', 'dims', 'checksum'];

    protected $casts = ['vector' => 'array', 'dims' => 'integer'];
}
