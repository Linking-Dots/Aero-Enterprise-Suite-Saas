<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_warehouses';

    protected $fillable = [
        'code', 'name', 'description', 'location', 'warehouse_type',
        'is_active', 'manager_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'manager_id' => 'integer',
    ];

    const TYPE_RAW_MATERIALS = 'raw_materials';

    const TYPE_WORK_IN_PROCESS = 'work_in_process';

    const TYPE_FINISHED_GOODS = 'finished_goods';

    const TYPE_RETURNS = 'returns';

    const TYPE_QUARANTINE = 'quarantine';

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function workOrderMaterials()
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }
}
