<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'location',
        'job_position',
        'period_from',
        'period_to',
        'description',
        'user_id', // Include user_id in fillable
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
