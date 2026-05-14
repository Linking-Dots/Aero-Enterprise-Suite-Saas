<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DisciplinaryCase extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'case_number',
        'employee_id',
        'action_type_id',
        'reported_by',
        'incident_date',
        'incident_description',
        'status',
        'investigation_notes',
        'action_taken',
        'action_date',
        'action_by',
        'employee_statement',
        'witness_statements',
        'appeal_filed',
        'appeal_notes',
        'closed_date',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'action_date' => 'date',
        'closed_date' => 'date',
        'appeal_filed' => 'boolean',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_ACTION_TAKEN = 'action_taken';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Get the employee for this case.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the action type for this case.
     */
    public function actionType(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryActionType::class, 'action_type_id');
    }

    /**
     * Get the user who reported this case.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user who took action.
     */
    public function actionTaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    /**
     * Get warnings related to this case.
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class, 'disciplinary_case_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInvestigating($query)
    {
        return $query->where('status', self::STATUS_INVESTIGATING);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Check if case can be closed
     */
    public function canBeClosed(): bool
    {
        return in_array($this->status, [self::STATUS_ACTION_TAKEN, self::STATUS_INVESTIGATING]);
    }

    /**
     * Check if case can be appealed
     */
    public function canBeAppealed(): bool
    {
        return $this->status === self::STATUS_ACTION_TAKEN && ! $this->appeal_filed;
    }

    /**
     * Generate unique case number
     */
    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $lastCase = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastCase ? (int) substr($lastCase->case_number, -4) + 1 : 1;

        return 'DC'.$year.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf', 'video/mp4'])
            ->maxFilesize(10 * 1024 * 1024); // 10MB
    }
}
