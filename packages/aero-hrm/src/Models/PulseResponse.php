<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PulseResponse extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_pulse_responses';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'survey_id',
        'respondent_hash',
        'answers',
        'demographics_snapshot',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'demographics_snapshot' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(PulseSurvey::class, 'survey_id');
    }
}
