<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobInterviewFeedback extends TenantModel
{
    use HasFactory;

    protected $table = 'job_interview_feedback';

    protected $fillable = [
        'interview_id',
        'interviewer_id',
        'technical_score',
        'communication_score',
        'cultural_fit_score',
        'problem_solving_score',
        'overall_score',
        'strengths',
        'weaknesses',
        'comments',
        'recommendation',
    ];

    protected $casts = [
        'technical_score' => 'integer',
        'communication_score' => 'integer',
        'cultural_fit_score' => 'integer',
        'problem_solving_score' => 'integer',
        'overall_score' => 'integer',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(JobInterview::class, 'interview_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
