<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_customers';

    protected $fillable = [
        'customer_number', 'company_name', 'first_name', 'last_name', 'email',
        'phone', 'mobile_phone', 'website', 'industry', 'customer_type',
        'billing_address', 'payment_terms', 'credit_limit', 'tax_exempt',
        'primary_contact_id', 'account_manager_id', 'status', 'notes',
    ];

    protected $casts = [
        'billing_address' => 'json',
        'credit_limit' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'primary_contact_id' => 'integer',
        'account_manager_id' => 'integer',
    ];

    const TYPE_COMMERCIAL = 'commercial';

    const TYPE_RESIDENTIAL = 'residential';

    const TYPE_GOVERNMENT = 'government';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    public function serviceLocations()
    {
        return $this->hasMany(ServiceLocation::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function workOrders()
    {
        return $this->hasMany(ServiceWorkOrder::class);
    }

    public function serviceAgreements()
    {
        return $this->hasMany(ServiceAgreement::class);
    }

    public function primaryContact()
    {
        return $this->belongsTo(CustomerContact::class, 'primary_contact_id');
    }

    public function accountManager()
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function getDisplayNameAttribute()
    {
        if ($this->customer_type === self::TYPE_COMMERCIAL) {
            return $this->company_name;
        }

        return trim($this->first_name.' '.$this->last_name);
    }

    public function getActiveServiceAgreementsAttribute()
    {
        return $this->serviceAgreements()->where('status', ServiceAgreement::STATUS_ACTIVE)->count();
    }

    public function getTotalEquipmentAttribute()
    {
        return $this->equipment()->count();
    }
}
