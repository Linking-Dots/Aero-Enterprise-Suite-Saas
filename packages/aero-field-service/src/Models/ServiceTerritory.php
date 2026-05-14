<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTerritory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_territories';

    protected $fillable = [
        'territory_name', 'territory_code', 'description', 'manager_id',
        'geographic_boundaries', 'zip_codes', 'is_active',
    ];

    protected $casts = [
        'manager_id' => 'integer',
        'geographic_boundaries' => 'json',
        'zip_codes' => 'json',
        'is_active' => 'boolean',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function technicians()
    {
        return $this->hasMany(Technician::class, 'territory_id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'field_service_territory_customers');
    }

    public function serviceLocations()
    {
        return $this->hasMany(ServiceLocation::class, 'territory_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActiveTechniciansCountAttribute()
    {
        return $this->technicians()->where('status', Technician::STATUS_ACTIVE)->count();
    }

    public function getTotalCustomersAttribute()
    {
        return $this->customers()->count();
    }
}
