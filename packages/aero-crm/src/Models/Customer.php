<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\HRM\Opportunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'company',
        'status',
        'notes',
        'customer_type',
        'assigned_to',
        'last_contact_date',
    ];

    protected $casts = [
        'last_contact_date' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function interactions()
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
