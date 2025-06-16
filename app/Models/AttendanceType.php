<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'config',
        'is_active',
        'priority',
        'required_permissions'
    ];

    protected $casts = [
        'config' => 'array',
        'required_permissions' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationship with users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Scope for active attendance types
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
