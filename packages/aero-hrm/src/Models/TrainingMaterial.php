<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TrainingMaterial extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'type',
        'url',
        'is_required',
        'order',
        'created_by',
        'visibility',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the training that this material belongs to.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the user who created the material.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the material is accessible by a specific user.
     */
    public function isAccessibleBy(User $user)
    {
        // Public materials are accessible by all enrolled users
        if ($this->visibility === 'public') {
            return true;
        }

        // Staff-only materials are accessible by trainers and admins
        if (
            $this->visibility === 'staff' &&
            ($user->id === $this->training->instructor_id ||
                $user->id === $this->training->created_by ||
                $user->hasPermissionTo('hr.training.manage'))
        ) {
            return true;
        }

        // Completed materials are only accessible by users who have completed prerequisites
        if ($this->visibility === 'completion_based') {
            // This would require additional logic to check completion status
            return false; // Simplified version
        }

        return false;
    }
}
