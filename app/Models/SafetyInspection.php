<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_date',
        'location',
        'inspector_id',
        'inspection_type', // routine, follow-up, pre-occupancy, special
        'status', // scheduled, in-progress, completed, cancelled
        'findings',
        'recommendations',
        'next_inspection_date',
        'notes',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_date' => 'date',
        'findings' => 'array',
        'recommendations' => 'array',
    ];

    /**
     * Get the user who conducted the inspection.
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Get the inspection items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SafetyInspectionItem::class, 'inspection_id');
    }
}
