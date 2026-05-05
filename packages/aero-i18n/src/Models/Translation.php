<?php

namespace Aero\I18n\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'language_code',
        'key',
        'value',
        'namespace',
        'group',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    public function scopeForLanguage(Builder $query, string $languageCode): Builder
    {
        return $query->where('language_code', $languageCode);
    }

    public function scopeForNamespace(Builder $query, ?string $namespace): Builder
    {
        if ($namespace) {
            return $query->where('namespace', $namespace);
        }
        return $query;
    }

    public function scopeForGroup(Builder $query, ?string $group): Builder
    {
        if ($group) {
            return $query->where('group', $group);
        }
        return $query;
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_custom', true);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_custom', false);
    }

    public function scopeMissingTranslation(Builder $query): Builder
    {
        return $query->whereNull('value')->orWhere('value', '');
    }
}
