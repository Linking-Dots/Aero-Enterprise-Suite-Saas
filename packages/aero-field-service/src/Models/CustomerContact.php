<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerContact extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_customer_contacts';

    protected $fillable = [
        'customer_id', 'first_name', 'last_name', 'title', 'department',
        'email', 'phone', 'mobile_phone', 'is_primary', 'can_authorize_work',
        'receive_notifications', 'preferred_contact_method', 'notes',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_primary' => 'boolean',
        'can_authorize_work' => 'boolean',
        'receive_notifications' => 'boolean',
    ];

    const CONTACT_EMAIL = 'email';

    const CONTACT_PHONE = 'phone';

    const CONTACT_MOBILE = 'mobile';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCanAuthorize($query)
    {
        return $query->where('can_authorize_work', true);
    }

    public function getPreferredContactAttribute()
    {
        switch ($this->preferred_contact_method) {
            case self::CONTACT_EMAIL:
                return $this->email;
            case self::CONTACT_PHONE:
                return $this->phone;
            case self::CONTACT_MOBILE:
                return $this->mobile_phone;
            default:
                return $this->email ?: $this->phone ?: $this->mobile_phone;
        }
    }
}
