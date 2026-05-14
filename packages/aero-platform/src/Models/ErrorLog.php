<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * ErrorLog Model
 *
 * Unified error logging for both backend (Laravel) and frontend (React/Inertia) errors.
 * Stored in central database for platform-wide visibility.
 * Receives errors from all installations (SaaS tenants and standalone).
 *
 * @property int $id
 * @property string $trace_id
 * @property string|null $source_domain - The domain where the error originated
 * @property string|null $license_key - The installation's license key
 * @property string|null $tenant_id
 * @property int|null $user_id
 * @property string $error_type
 * @property int $http_code
 * @property string|null $request_method
 * @property string $request_url
 * @property array|null $request_payload
 * @property string $error_message
 * @property string|null $stack_trace
 * @property string $origin
 * @property string|null $module
 * @property string|null $component
 * @property array|null $context
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property bool $is_resolved
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ErrorLog extends CentralModel
{
    use HasFactory;

    /**
     * Always use central database for error logs
     */
    protected $connection = 'central';

    protected $table = 'error_logs';

    protected $fillable = [
        'trace_id',
        'source_domain',
        'license_key',
        'tenant_id',
        'user_id',
        'error_type',
        'http_code',
        'request_method',
        'request_url',
        'request_payload',
        'error_message',
        'stack_trace',
        'origin',
        'module',
        'component',
        'context',
        'user_agent',
        'ip_address',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'http_code' => 'integer',
        'user_id' => 'integer',
        'resolved_by' => 'integer',
    ];

    /**
     * Hidden attributes for security
     */
    protected $hidden = [
        'stack_trace', // Don't expose stack traces in API responses
        'request_payload', // May contain sensitive data
    ];

    /**
     * Boot method to generate trace_id
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->trace_id)) {
                $model->trace_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope for unresolved errors
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for specific source domain (installation)
     */
    public function scopeFromDomain($query, string $domain)
    {
        return $query->where('source_domain', $domain);
    }

    /**
     * Scope for specific license key
     */
    public function scopeForLicense($query, string $licenseKey)
    {
        return $query->where('license_key', $licenseKey);
    }

    /**
     * Scope for specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for platform errors (no tenant)
     */
    public function scopePlatformErrors($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope for frontend errors
     */
    public function scopeFrontend($query)
    {
        return $query->where('origin', 'frontend');
    }

    /**
     * Scope for backend errors
     */
    public function scopeBackend($query)
    {
        return $query->where('origin', 'backend');
    }

    /**
     * Scope for specific HTTP code range
     */
    public function scopeHttpCodeRange($query, int $min, int $max)
    {
        return $query->whereBetween('http_code', [$min, $max]);
    }

    /**
     * Scope for server errors (5xx)
     */
    public function scopeServerErrors($query)
    {
        return $query->httpCodeRange(500, 599);
    }

    /**
     * Scope for client errors (4xx)
     */
    public function scopeClientErrors($query)
    {
        return $query->httpCodeRange(400, 499);
    }

    /**
     * Get sanitized payload (remove sensitive fields)
     */
    public function getSanitizedPayloadAttribute(): ?array
    {
        $payload = $this->request_payload;
        if (! $payload) {
            return null;
        }

        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_token', 'access_token', 'refresh_token',
            'secret', 'api_secret', 'client_secret',
            'credit_card', 'card_number', 'cvv', 'cvc',
            'ssn', 'social_security',
        ];

        return $this->sanitizeArray($payload, $sensitiveKeys);
    }

    /**
     * Recursively sanitize array
     */
    private function sanitizeArray(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $sensitiveKeys);
            }
        }

        return $data;
    }

    /**
     * Mark as resolved
     */
    public function markResolved(int $userId, ?string $notes = null): bool
    {
        return $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Relationship to user who resolved
     */
    public function resolver()
    {
        return $this->belongsTo(LandlordUser::class, 'resolved_by');
    }

    /**
     * Get user who caused the error
     */
    public function user()
    {
        // Could be either LandlordUser or Tenant User depending on context
        return $this->belongsTo(LandlordUser::class, 'user_id');
    }
}
