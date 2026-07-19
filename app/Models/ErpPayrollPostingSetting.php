<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpPayrollPostingSetting extends Model
{
    use HasFactory;

    protected $table = 'erp_payroll_posting_settings';

    protected $fillable = [
        'company_id',
        'salary_expense_account_id',
        'salary_payable_account_id',
        'allowance_expense_account_id',
        'deduction_account_id',
        'tds_payable_account_id',
        'pf_payable_account_id',
        'ssf_payable_account_id',
        'cit_payable_account_id',
        'advance_account_id',
        'loan_recovery_account_id',
        'statutory_rates',
    ];

    protected $casts = [
        'statutory_rates' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }
}
