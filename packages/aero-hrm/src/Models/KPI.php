<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KPI extends TenantModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kpis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'category',
        'target_value',
        'unit',
        'frequency',
        'formula',
        'data_source',
        'responsible_user_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value' => 'float',
    ];

    /**
     * Get the responsible user for the KPI.
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Get the KPI values.
     */
    public function values(): HasMany
    {
        return $this->hasMany(KPIValue::class);
    }

    /**
     * Get the current value of the KPI.
     */
    public function currentValue()
    {
        return $this->values()->latest()->first()?->value ?? 0;
    }

    /**
     * Calculate the trend of the KPI.
     */
    public function calculateTrend()
    {
        $values = $this->values()->latest()->take(2)->orderBy('date')->get();

        if ($values->count() < 2) {
            return 'stable';
        }

        $diff = $values->last()->value - $values->first()->value;

        if ($diff > 0) {
            return 'increasing';
        } elseif ($diff < 0) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
