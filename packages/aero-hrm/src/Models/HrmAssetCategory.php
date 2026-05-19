<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmAssetCategory extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_asset_categories';

    protected $fillable = ['name', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(HrmAsset::class, 'category_id');
    }
}
