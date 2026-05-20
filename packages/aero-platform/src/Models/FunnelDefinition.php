<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelDefinition extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'funnel_definitions';

    protected $fillable = ['name', 'steps', 'created_by'];

    protected function casts(): array
    {
        return ['steps' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'created_by');
    }
}
