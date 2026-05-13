<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_accounts';

    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'description',
        'currency', 'is_active', 'balance',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }
}
