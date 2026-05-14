<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\HRM\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceDocument extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'document_type', // policy, procedure, guideline, standard, regulation
        'content',
        'status', // draft, published, archived, under review
        'version',
        'published_date',
        'review_date',
        'approved_by',
        'department_id',
        'category_id',
        'file_path',
        'is_mandatory',
        'acknowledgement_required',
    ];

    protected $casts = [
        'published_date' => 'datetime',
        'review_date' => 'datetime',
        'is_mandatory' => 'boolean',
        'acknowledgement_required' => 'boolean',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(ComplianceCategory::class, 'category_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function acknowledgements()
    {
        return $this->hasMany(ComplianceAcknowledgement::class, 'document_id');
    }

    public function versions()
    {
        return $this->hasMany(ComplianceDocumentVersion::class, 'document_id');
    }

    public function audits()
    {
        return $this->hasMany(ComplianceAudit::class, 'document_id');
    }
}
