<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Leave Model
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $leave_setting_id
 * @property string $leave_type
 * @property string $from_date
 * @property string $to_date
 * @property int $no_of_days
 * @property int|null $approved_by
 * @property string $reason
 * @property string $status
 * @property array|null $approval_chain
 * @property int $current_approval_level
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property int|null $rejected_by
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User $user
 * @property-read User $employee Note: Returns User model, not Employee
 * @property-read LeaveSetting|null $leaveSetting
 * @property-read User|null $approver
 * @property-read string $status_color
 */
class Leave extends TenantModel
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Aero\HRM\Database\Factories\LeaveFactory::new();
    }

    protected $fillable = [
        'user_id',
        'leave_setting_id',
        'leave_type',
        'from_date',
        'to_date',
        'no_of_days',
        'approved_by',
        'reason',
        'status',
        'approval_chain',
        'current_approval_level',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'submitted_at',
        'workflow_instance_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'leave_setting_id' => 'integer',
        'leave_type' => 'string',
        'from_date' => 'date', // Simplified casting
        'to_date' => 'date',   // Simplified casting
        'no_of_days' => 'integer',
        'reason' => 'string',
        'status' => 'string',
        'approved_by' => 'integer',
        'approval_chain' => 'array',
        'current_approval_level' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Simplified date serialization to ensure consistent date format
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Ensure dates are in consistent Y-m-d format
        if (isset($array['from_date'])) {
            $array['from_date'] = Carbon::parse($array['from_date'])->format('Y-m-d');
        }

        if (isset($array['to_date'])) {
            $array['to_date'] = Carbon::parse($array['to_date'])->format('Y-m-d');
        }

        return $array;
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveSetting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeaveSetting::class, 'leave_type');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function workflowInstance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Aero\Workflow\Models\WorkflowInstance::class, 'workflow_instance_id');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'New' => 'primary',
            'Pending' => 'warning',
            'Approved' => 'success',
            'Declined' => 'danger',
            default => 'default'
        };
    }

    /**
     * Get the from_date attribute in consistent Y-m-d format.
     *
     * @param  string  $value
     * @return string
     */
    public function getFromDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : $value;
    }

    /**
     * Get the to_date attribute in consistent Y-m-d format.
     *
     * @param  string  $value
     * @return string
     */
    public function getToDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : $value;
    }
}
