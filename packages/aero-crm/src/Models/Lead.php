<?php

namespace Aero\Crm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'source_id',
        'status',
        'assigned_to',
        'created_by',
        'notes',
        'score',
        'last_contacted_at',
        'converted_at',
        'customer_id',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
        'score' => 'integer',
    ];

    /**
     * Lead statuses
     */
    const STATUS_NEW = 'new';

    const STATUS_CONTACTED = 'contacted';

    const STATUS_QUALIFIED = 'qualified';

    const STATUS_CONVERTED = 'converted';

    const STATUS_LOST = 'lost';

    /**
     * Get the source that owns the lead
     */
    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /**
     * Get the user assigned to the lead
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the lead
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the customer this lead was converted to
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope for qualified leads
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope for converted leads
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Check if lead is qualified
     */
    public function isQualified()
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    /**
     * Check if lead is converted
     */
    public function isConverted()
    {
        return $this->status === self::STATUS_CONVERTED;
    }
}
