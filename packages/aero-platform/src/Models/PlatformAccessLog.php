<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Models\CentralModel;

/**
 * Platform Access Log — immutable record of sensitive-field accesses.
 *
 * Lives on the central (landlord) DB in platform_access_logs.
 * Schema: id, actor_id, actor_name, actor_ip, actor_user_agent,
 *         resource_type, resource_id, subject_label, fields_accessed (json), created_at
 */
class PlatformAccessLog extends CentralModel
{
    protected $table = 'platform_access_logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_ip',
        'actor_user_agent',
        'resource_type',
        'resource_id',
        'subject_label',
        'fields_accessed',
        'created_at',
    ];

    protected $casts = [
        'fields_accessed' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * PII fields tracked across the platform.
     * Keep in sync with EncryptedField casts on all models.
     */
    public const PII_FIELDS = [
        'account_number',
        'routing_number',
        'tax_id',
        'national_id',
        'medical_notes',
        'byoc_db_host',
        'byoc_db_username',
        'byoc_db_password',
    ];

    /**
     * Scope: only rows where fields_accessed contains at least one PII field.
     */
    public function scopePiiOnly($query)
    {
        return $query->where(function ($q) {
            foreach (self::PII_FIELDS as $field) {
                $q->orWhereJsonContains('fields_accessed', $field);
            }
        });
    }

    public function scopeByResourceType($query, ?string $type)
    {
        return $type ? $query->where('resource_type', $type) : $query;
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}
