<?php

namespace Aero\Platform\Models;

class BulkOperation extends CentralModel
{
    protected $table = 'bulk_operations';

    protected $fillable = ['type', 'payload', 'status', 'created_by', 'total', 'processed'];

    protected $casts = ['payload' => 'array'];
}
