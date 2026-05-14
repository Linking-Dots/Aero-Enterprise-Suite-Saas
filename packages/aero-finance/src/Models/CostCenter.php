<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_cost_centers';

    protected $fillable = [
        'code', 'name', 'description', 'parent_id',
        'cost_center_type', 'is_active', 'manager_id',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'manager_id' => 'integer',
    ];

    const TYPE_DEPARTMENT = 'department';

    const TYPE_PROJECT = 'project';

    const TYPE_LOCATION = 'location';

    const TYPE_PRODUCT_LINE = 'product_line';

    public function parent()
    {
        return $this->belongsTo(CostCenter::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CostCenter::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }
}
