<?php

declare(strict_types=1);

namespace Aero\Forms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Form Model
 *
 * Represents a form created with the drag-drop form builder.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property array $schema
 * @property array|null $conditional_logic
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property bool $allow_multiple_submissions
 * @property bool $require_authentication
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $max_submissions
 * @property string|null $success_message
 * @property string|null $redirect_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Form extends TenantModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'schema',
        'conditional_logic',
        'is_published',
        'published_at',
        'allow_multiple_submissions',
        'require_authentication',
        'expires_at',
        'max_submissions',
        'success_message',
        'redirect_url',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'schema' => 'array',
        'conditional_logic' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'allow_multiple_submissions' => 'boolean',
        'require_authentication' => 'boolean',
        'expires_at' => 'datetime',
        'max_submissions' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Form $form): void {
            if ($form->is_published && !$form->published_at) {
                $form->published_at = now();
            }
        });

        static::updating(function (Form $form): void {
            if ($form->is_published && $form->isDirty('is_published') && !$form->published_at) {
                $form->published_at = now();
            }
        });
    }

    /**
     * Relationship: User who created this form.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Form submissions.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Scope: only published forms.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: forms by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the form is accepting submissions.
     */
    public function isAcceptingSubmissions(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_submissions && $this->submissions()->count() >= $this->max_submissions) {
            return false;
        }

        return true;
    }

    /**
     * Get submission count.
     */
    public function getSubmissionCountAttribute(): int
    {
        return $this->submissions()->count();
    }
}
