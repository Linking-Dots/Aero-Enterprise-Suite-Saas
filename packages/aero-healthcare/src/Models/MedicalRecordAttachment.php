<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecordAttachment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_medical_record_attachments';

    protected $fillable = [
        'medical_record_id', 'file_name', 'file_path', 'file_type',
        'file_size', 'mime_type', 'description', 'is_confidential',
        'uploaded_by',
    ];

    protected $casts = [
        'medical_record_id' => 'integer',
        'file_size' => 'integer',
        'is_confidential' => 'boolean',
        'uploaded_by' => 'integer',
    ];

    const TYPE_IMAGE = 'image';

    const TYPE_PDF = 'pdf';

    const TYPE_DOCUMENT = 'document';

    const TYPE_XRAY = 'xray';

    const TYPE_LAB_REPORT = 'lab_report';

    const TYPE_SCAN = 'scan';

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/'.$this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    public function isImage()
    {
        return in_array($this->file_type, [self::TYPE_IMAGE, self::TYPE_XRAY, self::TYPE_SCAN]);
    }

    public function isPdf()
    {
        return $this->file_type === self::TYPE_PDF;
    }

    public function scopeImages($query)
    {
        return $query->whereIn('file_type', [self::TYPE_IMAGE, self::TYPE_XRAY, self::TYPE_SCAN]);
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }
}
