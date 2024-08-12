<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'location',
        'job_position',
        'period_from',
        'period_to',
        'description',
        'user_id', // Include user_id in fillable
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
