<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobHiringStage extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_id',
        'name',
        'description',
        'sequence',
        'stage_order',       // kept for backward compat — mirrors sequence
        'is_active',
        'required_actions',
        'requires_approval',
        'is_final',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'is_final' => 'boolean',
        'required_actions' => 'array',
    ];

    /**
     * Get the job this stage belongs to.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the applications currently at this stage.
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'current_stage_id');
    }

    /**
     * Get the stage history entries for this stage.
     */
    public function stageHistories()
    {
        return $this->hasMany(JobApplicationStageHistory::class, 'stage_id');
    }

    /**
     * Get the next stage in the hiring process.
     */
    public function nextStage()
    {
        return JobHiringStage::where('job_id', $this->job_id)
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence', 'asc')
            ->first();
    }

    /**
     * Get the previous stage in the hiring process.
     */
    public function previousStage()
    {
        return JobHiringStage::where('job_id', $this->job_id)
            ->where('sequence', '<', $this->sequence)
            ->orderBy('sequence', 'desc')
            ->first();
    }

    /**
     * Move an application to the next stage.
     */
    public function moveApplicationToNextStage(JobApplication $application, $notes = null)
    {
        $nextStage = $this->nextStage();

        if (! $nextStage) {
            return false;
        }

        // Create stage history entry
        JobApplicationStageHistory::create([
            'application_id' => $application->id,
            'from_stage_id' => $this->id,
            'to_stage_id' => $nextStage->id,
            'moved_by' => auth()->id(),
            'moved_at' => now(),
            'notes' => $notes,
        ]);

        // Update application
        $application->update([
            'current_stage_id' => $nextStage->id,
            'last_status_change' => now(),
        ]);

        return true;
    }
}
