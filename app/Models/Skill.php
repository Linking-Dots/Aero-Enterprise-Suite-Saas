<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'type', // technical, soft-skill, certification, language, etc.
        'status', // active, inactive
    ];

    /**
     * Get the employees with this skill.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_skills')
            ->withPivot('proficiency_level', 'acquired_date', 'expires_at', 'notes', 'is_verified', 'verified_by', 'verified_at')
            ->withTimestamps();
    }

    /**
     * Get competencies associated with this skill.
     */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'competency_skills');
    }
}
