<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecution extends CentralModel
{
    protected $fillable = [
        'scheduled_report_id',
        'status',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'record_count',
        'file_path',
        'file_size_kb',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time_ms' => 'integer',
        'record_count' => 'integer',
        'file_size_kb' => 'integer',
        'retry_count' => 'integer',
    ];

    /**
     * Get the scheduled report that owns this execution.
     */
    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    /**
     * Scope to get only successful executions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get only failed executions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get recent executions.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark execution as started.
     */
    public function markStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark execution as completed successfully.
     */
    public function markCompleted(string $filePath, int $recordCount, int $fileSizeKb): void
    {
        $executionTime = $this->started_at
            ? now()->diffInMilliseconds($this->started_at)
            : null;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time_ms' => $executionTime,
            'record_count' => $recordCount,
            'file_path' => $filePath,
            'file_size_kb' => $fileSizeKb,
        ]);
    }

    /**
     * Mark execution as failed.
     */
    public function markFailed(string $errorMessage): void
    {
        $executionTime = $this->started_at
            ? now()->diffInMilliseconds($this->started_at)
            : null;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'execution_time_ms' => $executionTime,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if this execution can be retried.
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }

    /**
     * Get execution duration in seconds.
     */
    public function getDurationInSeconds(): ?float
    {
        return $this->execution_time_ms ? $this->execution_time_ms / 1000 : null;
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSize(): ?string
    {
        if (! $this->file_size_kb) {
            return null;
        }

        if ($this->file_size_kb < 1024) {
            return $this->file_size_kb.' KB';
        }

        return round($this->file_size_kb / 1024, 2).' MB';
    }
}
