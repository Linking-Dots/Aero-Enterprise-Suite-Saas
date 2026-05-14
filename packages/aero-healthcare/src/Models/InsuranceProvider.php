<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceProvider extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_insurance_providers';

    protected $fillable = [
        'name', 'code', 'type', 'contact_phone', 'contact_email', 'website',
        'address', 'payer_id', 'is_active', 'eligibility_check_enabled',
        'copay_amount', 'deductible_amount', 'coverage_details', 'created_by',
    ];

    protected $casts = [
        'address' => 'json',
        'is_active' => 'boolean',
        'eligibility_check_enabled' => 'boolean',
        'copay_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'coverage_details' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_PPO = 'ppo';

    const TYPE_HMO = 'hmo';

    const TYPE_EPO = 'epo';

    const TYPE_POS = 'pos';

    const TYPE_MEDICARE = 'medicare';

    const TYPE_MEDICAID = 'medicaid';

    const TYPE_PRIVATE = 'private';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryPatients()
    {
        return $this->hasMany(Patient::class, 'insurance_primary_id');
    }

    public function secondaryPatients()
    {
        return $this->hasMany(Patient::class, 'insurance_secondary_id');
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function getFormattedAddressAttribute()
    {
        if (! $this->address) {
            return null;
        }

        $addr = $this->address;
        $lines = [];

        if (isset($addr['street'])) {
            $lines[] = $addr['street'];
        }

        $cityState = [];
        if (isset($addr['city'])) {
            $cityState[] = $addr['city'];
        }
        if (isset($addr['state'])) {
            $cityState[] = $addr['state'];
        }
        if (isset($addr['zip'])) {
            $cityState[] = $addr['zip'];
        }

        if ($cityState) {
            $lines[] = implode(', ', $cityState);
        }

        return implode('\n', $lines);
    }

    public function getTotalPatientsAttribute()
    {
        return $this->primaryPatients()->count() + $this->secondaryPatients()->count();
    }

    public function supportsCoverage($serviceType)
    {
        if (! $this->coverage_details) {
            return false;
        }

        return isset($this->coverage_details[$serviceType]) &&
               $this->coverage_details[$serviceType]['covered'] === true;
    }

    public function getCoveragePercentage($serviceType)
    {
        if (! $this->supportsCoverage($serviceType)) {
            return 0;
        }

        return $this->coverage_details[$serviceType]['percentage'] ?? 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
