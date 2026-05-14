<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use App\Models\Tenant\DMS\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HrDocument extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'document_type', // policy, procedure, form, template, contract, certificate, other
        'category_id',
        'description',
        'file_path',
        'version',
        'effective_date',
        'expiry_date',
        'status', // draft, active, archived, expired
        'is_confidential',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_confidential' => 'boolean',
    ];

    /**
     * Get the category that the document belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    /**
     * Get the user who created the document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employees associated with this document.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_documents')
            ->withPivot('acknowledgment_status', 'acknowledgment_date', 'notes')
            ->withTimestamps();
    }
}
