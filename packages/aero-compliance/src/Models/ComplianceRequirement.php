<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRequirement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'source',
        'reference_number',
        'applicable',
        'status',
        'compliance_evidence',
        'responsible_person_id',
        'last_evaluation_date',
        'next_evaluation_date',
    ];

    protected $casts = [
        'applicable' => 'boolean',
        'last_evaluation_date' => 'date',
        'next_evaluation_date' => 'date',
    ];

    /**
     * Get the responsible person for this requirement.
     */
    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Check if evaluation is due.
     */
    public function isEvaluationDue()
    {
        return $this->next_evaluation_date && now()->greaterThanOrEqualTo($this->next_evaluation_date);
    }

    /**
     * Get related documents.
     */
    public function documents()
    {
        return $this->belongsToMany(ComplianceDocument::class, 'compliance_requirement_documents', 'requirement_id', 'document_id');
    }
}
