<?php

namespace Aero\Dms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends TenantModel
{
    use HasFactory;

    protected $table = 'dms_document_versions';

    protected $fillable = [
        'document_id',
        'version',
        'change_summary',
        'file_path',
        'file_size',
        'checksum',
        'created_by',
    ];

    /**
     * Get the document that owns this version.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
