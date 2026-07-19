<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpPayrollRunLine extends Model
{
    use HasFactory;

    protected $table = 'erp_payroll_run_lines';

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'employee_name', 'department', 'designation', 'pan', 'ssf_number',
        'basic_salary', 'prorated_basic', 'allowances', 'gross_salary', 'deductions', 'tds', 'pf',
        'ssf_employee', 'ssf_employer', 'cit', 'advance_recovery', 'loan_recovery', 'net_payable',
        'present_days', 'leave_days', 'unpaid_days', 'payable_days', 'component_breakdown',
    ];

    protected $casts = [
        'basic_salary' => 'float', 'prorated_basic' => 'float', 'allowances' => 'float',
        'gross_salary' => 'float', 'deductions' => 'float', 'tds' => 'float', 'pf' => 'float',
        'ssf_employee' => 'float', 'ssf_employer' => 'float', 'cit' => 'float', 'advance_recovery' => 'float',
        'loan_recovery' => 'float', 'net_payable' => 'float', 'present_days' => 'integer',
        'leave_days' => 'integer', 'unpaid_days' => 'integer', 'payable_days' => 'integer',
        'component_breakdown' => 'array',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(ErpPayrollRun::class, 'payroll_run_id');
    }
}
