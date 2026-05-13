<?php

namespace Aero\Compliance\Models;

use Aero\Rfi\Models\Rfi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jurisdiction extends TenantModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location',
        'start_chainage',
        'end_chainage',
        'incharge',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who is in charge of this jurisdiction/work location.
     */
    public function inchargeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incharge');
    }

    /**
     * Get all RFIs associated with this jurisdiction.
     */
    public function rfis()
    {
        return $this->hasMany(Rfi::class, 'jurisdiction_id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function dailyWorks()
    {
        return $this->rfis();
    }
}
