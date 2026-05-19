<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmExpenseCategory extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_expense_categories';

    protected $fillable = ['name', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(HrmExpenseClaimItem::class, 'category_id');
    }
}
