<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportExecution extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_report_executions';

    protected $fillable = [
        'report_id', 'executed_by', 'execution_type', 'parameters',
        'status', 'started_at', 'completed_at', 'duration_seconds',
        'output_format', 'output_file_path', 'row_count', 'error_message',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'executed_by' => 'integer',
        'parameters' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'row_count' => 'integer',
    ];

    const TYPE_MANUAL = 'manual';

    const TYPE_SCHEDULED = 'scheduled';

    const TYPE_API = 'api';

    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const FORMAT_PDF = 'pdf';

    const FORMAT_EXCEL = 'excel';

    const FORMAT_CSV = 'csv';

    const FORMAT_JSON = 'json';

    const FORMAT_HTML = 'html';

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }
}
