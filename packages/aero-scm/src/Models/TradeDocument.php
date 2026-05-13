<?php

namespace Aero\Scm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradeDocument extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_number',
        'document_type',
        'trade_transaction_type',
        'trade_transaction_id',
        'document_name',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'issue_date',
        'expiry_date',
        'issued_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the trade transaction (polymorphic).
     */
    public function tradeTransaction()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get documents by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to get documents by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if document is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < now()->toDateString();
    }

    /**
     * Check if document is expiring soon.
     */
    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date &&
            $this->expiry_date <= now()->addDays($days)->toDateString() &&
            $this->expiry_date >= now()->toDateString();
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute()
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }
}
