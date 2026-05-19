<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmDisciplinaryCaseDocument extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_disciplinary_case_documents';

    protected $fillable = ['case_id', 'disk', 'path', 'original_name', 'size_bytes', 'uploaded_by'];

    public function case(): BelongsTo
    {
        return $this->belongsTo(HrmDisciplinaryCase::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
