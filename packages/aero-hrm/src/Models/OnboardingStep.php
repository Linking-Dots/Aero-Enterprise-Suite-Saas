<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class OnboardingStep extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'order', 'active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (Auth::id()) {
                $model->created_by = Auth::id();
            }
            if ($model->order === null) {
                $model->order = static::max('order') + 1;
            }
            if ($model->active === null) {
                $model->active = true;
            }
        });
        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
