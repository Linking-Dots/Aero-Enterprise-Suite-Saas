<?php

namespace Aero\Notifications\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'subject',
        'html_content',
        'plain_content',
        'variables',
        'category',
        'is_system',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The tenant that owns the email template.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    /**
     * Get the creator of the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for user templates.
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Render the template with variables.
     */
    public function render(array $data = []): string
    {
        $content = $this->html_content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
            $content = str_replace("{{{$key}}}", $value, $content);
            $content = str_replace("{{ $key }}", $value, $content);
            $content = str_replace("{{{ $key }}}", $value, $content);
        }

        return $content;
    }

    /**
     * Render the subject with variables.
     */
    public function renderSubject(array $data = []): string
    {
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $subject = str_replace("{{{$key}}}", $value, $subject);
            $subject = str_replace("{{ $key }}", $value, $subject);
            $subject = str_replace("{{{ $key }}}", $value, $subject);
        }

        return $subject;
    }

    /**
     * Extract variables from template.
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $this->html_content, $matches);
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $this->subject, $subjectMatches);

        return array_unique(array_merge($matches[1] ?? [], $subjectMatches[1] ?? []));
    }
}
