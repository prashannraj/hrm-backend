<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpPayrollPostingReversal extends Model
{
    use HasFactory;

    protected $table = 'erp_payroll_posting_reversals';

    protected $fillable = [
        'payroll_run_id',
        'original_journal_voucher_id',
        'reversal_journal_voucher_id',
        'reason',
        'reversed_by',
        'reversed_at',
    ];

    protected $casts = [
        'reversed_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(ErpPayrollRun::class, 'payroll_run_id');
    }
}
