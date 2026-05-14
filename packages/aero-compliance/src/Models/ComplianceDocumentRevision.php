<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceDocumentRevision extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'change_summary',
        'file_path',
        'created_by',
    ];

    /**
     * Get the document that owns the revision.
     */
    public function document()
    {
        return $this->belongsTo(ComplianceDocument::class, 'document_id');
    }

    /**
     * Get the user who created the revision.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
