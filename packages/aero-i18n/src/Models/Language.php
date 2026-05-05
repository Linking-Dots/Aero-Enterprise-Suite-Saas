<?php

namespace Aero\I18n\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'is_enabled',
        'is_rtl',
        'direction',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_rtl' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'language_code', 'code');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    public function scopeRtl(Builder $query): Builder
    {
        return $query->where('is_rtl', true);
    }

    public function scopeLtr(Builder $query): Builder
    {
        return $query->where('is_rtl', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
