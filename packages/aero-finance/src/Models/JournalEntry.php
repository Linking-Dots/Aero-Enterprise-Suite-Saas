<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_journal_entries';

    protected $fillable = [
        'date',
        'reference',
        'description',
        'type',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'created_by' => 'integer',
        'approved_by' => 'integer',
    ];

    public const TYPE_STANDARD = 'standard';

    public const TYPE_ADJUSTING = 'adjusting';

    public const TYPE_CLOSING = 'closing';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_VOIDED = 'voided';

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isBalanced(): bool
    {
        $totalDebit = $this->lines()->sum('debit');
        $totalCredit = $this->lines()->sum('credit');

        return abs($totalDebit - $totalCredit) < 0.01;
    }
}
