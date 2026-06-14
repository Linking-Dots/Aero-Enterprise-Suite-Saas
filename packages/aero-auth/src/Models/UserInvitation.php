<?php

namespace Aero\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $fillable = [
        'email',
        'name',
        'token',
        'roles',
        'metadata',
        'invited_by',
        'expires_at',
        'accepted_at',
        'invited_from_ip',
    ];

    protected $casts = [
        'roles' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Default audit label (mirrors the value previously inherited from TenantModel;
     * auth is now context-free, so identity models are plain Models — same pattern
     * as {@see User}/{@see UserDevice}/{@see TenantInvitation}). Required by
     * AuditService::log(), which calls $subject->getAuditLabel() on the audit subject
     * (e.g. InvitationController::accept() passes the invitation as subject).
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    /**
     * Get the user who sent the invitation
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if invitation has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation has been accepted
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if invitation is still valid
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isAccepted();
    }

    /**
     * Mark invitation as accepted
     */
    public function markAsAccepted(): bool
    {
        return $this->update(['accepted_at' => now()]);
    }

    /**
     * Scope to get pending invitations
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '<=', now());
    }
}
