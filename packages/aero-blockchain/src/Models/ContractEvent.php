<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractEvent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_contract_events';

    protected $fillable = [
        'smart_contract_id', 'transaction_id', 'event_name', 'event_signature',
        'log_index', 'block_number', 'transaction_index', 'topics', 'data',
        'decoded_data', 'timestamp', 'is_indexed',
    ];

    protected $casts = [
        'smart_contract_id' => 'integer',
        'transaction_id' => 'integer',
        'log_index' => 'integer',
        'block_number' => 'integer',
        'transaction_index' => 'integer',
        'topics' => 'json',
        'decoded_data' => 'json',
        'timestamp' => 'datetime',
        'is_indexed' => 'boolean',
    ];

    public function smartContract()
    {
        return $this->belongsTo(SmartContract::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getFormattedDataAttribute()
    {
        if (is_array($this->decoded_data)) {
            return json_encode($this->decoded_data, JSON_PRETTY_PRINT);
        }

        return $this->data;
    }

    public function getTopicCountAttribute()
    {
        return is_array($this->topics) ? count($this->topics) : 0;
    }

    public function hasParameter($paramName)
    {
        return isset($this->decoded_data[$paramName]);
    }

    public function getParameter($paramName)
    {
        return $this->decoded_data[$paramName] ?? null;
    }

    public function getAgeAttribute()
    {
        return $this->timestamp ? $this->timestamp->diffForHumans() : null;
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('smart_contract_id', $contractId);
    }

    public function scopeByEvent($query, $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeByBlock($query, $blockNumber)
    {
        return $query->where('block_number', $blockNumber);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeIndexed($query)
    {
        return $query->where('is_indexed', true);
    }
}
