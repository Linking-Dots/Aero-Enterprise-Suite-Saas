<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'institution',
        'subject',
        'starting_date',
        'complete_date',
        'degree',
        'grade',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
