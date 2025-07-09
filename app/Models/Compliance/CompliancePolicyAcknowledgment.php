<?php

namespace App\Models\Compliance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompliancePolicyAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'user_id',
        'acknowledged_at',
        'acknowledgment_method',
        'notes',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Get the policy.
     */
    public function policy()
    {
        return $this->belongsTo(CompliancePolicy::class, 'policy_id');
    }

    /**
     * Get the user who acknowledged.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get acknowledgments by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('acknowledgment_method', $method);
    }
}
