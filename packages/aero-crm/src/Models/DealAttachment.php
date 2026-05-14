<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealAttachment extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'deal_id',
        'uploaded_by',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'category',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Categories
     */
    const CATEGORY_PROPOSAL = 'proposal';

    const CATEGORY_CONTRACT = 'contract';

    const CATEGORY_INVOICE = 'invoice';

    const CATEGORY_OTHER = 'other';

    /**
     * Get the deal
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the user who uploaded
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get human readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
