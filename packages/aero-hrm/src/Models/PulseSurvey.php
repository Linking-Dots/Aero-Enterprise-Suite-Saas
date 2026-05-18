<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PulseSurvey extends TenantModel
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'hrm_pulse_surveys';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'questions',
        'audience_filter',
        'status',
        'opens_at',
        'closes_at',
        'anonymous',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'audience_filter' => 'array',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'anonymous' => 'boolean',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PulseResponse::class, 'survey_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
