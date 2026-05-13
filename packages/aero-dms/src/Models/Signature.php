<?php

namespace Aero\Dms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends TenantModel
{
    use HasFactory;

    protected $table = 'dms_signatures';

    protected $fillable = [
        'document_id',
        'signer_id',
        'signature_data',
        'signature_type',
        'certificate_fingerprint',
        'metadata',
        'signed_at',
        'is_valid',
    ];

    protected $casts = [
        'metadata' => 'array',
        'signed_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    /**
     * Get the document this signature belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Get the user who signed the document.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    /**
     * Scope for valid signatures.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope by signature type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('signature_type', $type);
    }

    /**
     * Invalidate this signature.
     */
    public function invalidate(): void
    {
        $this->update(['is_valid' => false]);
    }

    /**
     * Validate this signature.
     */
    public function validate(): void
    {
        $this->update(['is_valid' => true]);
    }
}
