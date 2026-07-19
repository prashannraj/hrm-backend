<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpPayrollRun extends Model
{
    use HasFactory;

    protected $table = 'erp_payroll_runs';

    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'journal_voucher_id', 'payroll_number',
        'period_month', 'period_from', 'period_to', 'posting_date_ad', 'posting_date_bs', 'status',
        'gross_salary', 'salary_expense', 'allowances', 'deductions', 'tds', 'pf', 'ssf_employee',
        'ssf_employer', 'cit', 'advance_recovery', 'loan_recovery', 'net_payable', 'attendance_summary',
        'leave_summary', 'posted_at', 'locked_at', 'created_by', 'posted_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'posting_date_ad' => 'date',
        'posted_at' => 'datetime',
        'locked_at' => 'datetime',
        'gross_salary' => 'float',
        'salary_expense' => 'float',
        'allowances' => 'float',
        'deductions' => 'float',
        'tds' => 'float',
        'pf' => 'float',
        'ssf_employee' => 'float',
        'ssf_employer' => 'float',
        'cit' => 'float',
        'advance_recovery' => 'float',
        'loan_recovery' => 'float',
        'net_payable' => 'float',
        'attendance_summary' => 'array',
        'leave_summary' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ErpPayrollRunLine::class, 'payroll_run_id');
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id');
    }
}
