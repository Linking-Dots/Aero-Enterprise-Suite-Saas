<?php

declare(strict_types=1);

namespace Aero\Forms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Form Submission Model
 *
 * Represents a submission to a form.
 *
 * @property int $id
 * @property int $form_id
 * @property int|null $user_id
 * @property array $data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class FormSubmission extends TenantModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'form_id',
        'user_id',
        'data',
        'ip_address',
        'user_agent',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Relationship: Form that this submission belongs to.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Relationship: User who submitted the form (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: submissions by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: submissions for a form.
     */
    public function scopeForForm($query, $formId)
    {
        return $query->where('form_id', $formId);
    }

    /**
     * Scope: submissions by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
