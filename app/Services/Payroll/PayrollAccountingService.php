<?php

namespace App\Services\Payroll;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\ErpPayrollPostingReversal;
use App\Models\ErpPayrollPostingSetting;
use App\Models\ErpPayrollRun;
use App\Models\LeaveRequest;
use App\Services\Accounting\JournalVoucherService;
use App\Services\Accounting\VoucherOperationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollAccountingService
{
    public function __construct(
        private readonly JournalVoucherService $journalVoucherService,
        private readonly VoucherOperationService $voucherOperationService
    ) {
    }

    public function dashboard(int $companyId): array
    {
        return [
            'runs' => ErpPayrollRun::where('company_id', $companyId)->count(),
            'posted_runs' => ErpPayrollRun::where('company_id', $companyId)->where('status', 'posted')->count(),
            'gross_salary' => round((float) ErpPayrollRun::where('company_id', $companyId)->sum('gross_salary'), 2),
            'net_payable' => round((float) ErpPayrollRun::where('company_id', $companyId)->sum('net_payable'), 2),
            'statutory_payable' => round((float) ErpPayrollRun::where('company_id', $companyId)->selectRaw('sum(tds + pf + ssf_employee + ssf_employer + cit) as total')->value('total'), 2),
        ];
    }

    public function saveSettings(array $data): ErpPayrollPostingSetting
    {
        return ErpPayrollPostingSetting::updateOrCreate(
            ['company_id' => $data['company_id']],
            array_merge($data, [
                'statutory_rates' => $data['statutory_rates'] ?? [
                    'ssf_employee' => 11,
                    'ssf_employer' => 20,
                    'pf' => 0,
                ],
            ])
        );
    }

    public function generateRun(array $data, array $context, ?int $userId = null): ErpPayrollRun
    {
        $settings = ErpPayrollPostingSetting::where('company_id', $context['company_id'])->first();
        if (! $settings) {
            throw ValidationException::withMessages(['settings' => 'Payroll posting settings must be configured before generating payroll.']);
        }

        $periodFrom = Carbon::parse($data['period_from']);
        $periodTo = Carbon::parse($data['period_to']);
        $periodMonth = $data['period_month'] ?? $periodFrom->format('Y-m');

        return DB::transaction(function () use ($data, $context, $userId, $settings, $periodFrom, $periodTo, $periodMonth) {
            $run = ErpPayrollRun::create(array_merge($context, [
                'payroll_number' => $data['payroll_number'] ?? 'PAY-' . str_replace('-', '', $periodMonth),
                'period_month' => $periodMonth,
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
                'posting_date_ad' => $data['posting_date_ad'] ?? $periodTo->toDateString(),
                'posting_date_bs' => $data['posting_date_bs'] ?? null,
                'status' => 'draft',
                'created_by' => $userId,
            ]));

            $employees = Employee::orderBy('id')->get();
            $totals = $this->buildRunLines($run, $employees, $periodFrom, $periodTo, $settings, $data);
            $run->update($totals);

            if (($data['post_now'] ?? false) === true) {
                return $this->postRun($run, $context, $userId, $data);
            }

            return $run->fresh(['lines', 'journalVoucher']);
        });
    }

    public function postRun(ErpPayrollRun $run, array $context, ?int $userId = null, array $data = []): ErpPayrollRun
    {
        if ($run->status === 'locked') {
            throw ValidationException::withMessages(['status' => 'Locked payroll runs cannot be posted again.']);
        }

        if ($run->status === 'posted') {
            return $run->load(['lines', 'journalVoucher.lines.account']);
        }

        $settings = ErpPayrollPostingSetting::where('company_id', $run->company_id)->firstOrFail();
        $voucher = $this->journalVoucherService->create([
            'company_id' => $run->company_id,
            'branch_id' => $run->branch_id,
            'fiscal_year_id' => $run->fiscal_year_id,
            'voucher_type' => 'payroll',
            'voucher_date_ad' => $run->posting_date_ad->format('Y-m-d'),
            'voucher_date_bs' => $run->posting_date_bs,
            'narration' => 'Payroll accounting posting for ' . $run->period_month,
            'post_now' => true,
            'lines' => $this->journalLines($run, $settings),
        ], $userId);

        $run->update([
            'journal_voucher_id' => $voucher->id,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $userId,
        ]);

        return $run->fresh(['lines', 'journalVoucher.lines.account']);
    }

    public function lockRun(ErpPayrollRun $run): ErpPayrollRun
    {
        if ($run->status !== 'posted') {
            throw ValidationException::withMessages(['status' => 'Only posted payroll runs can be locked.']);
        }

        $run->update(['status' => 'locked', 'locked_at' => now()]);

        return $run->fresh(['lines', 'journalVoucher']);
    }

    public function reverseRun(ErpPayrollRun $run, ?int $userId = null, string $reason = ''): ErpPayrollPostingReversal
    {
        if (! $run->journal_voucher_id || ! in_array($run->status, ['posted', 'locked'], true)) {
            throw ValidationException::withMessages(['status' => 'Only posted or locked payroll runs with a voucher can be reversed.']);
        }

        return DB::transaction(function () use ($run, $userId, $reason) {
            $reversalVoucher = $this->voucherOperationService->reverse($run->journalVoucher, $userId, $reason);

            $run->update(['status' => 'reversed']);

            return ErpPayrollPostingReversal::create([
                'payroll_run_id' => $run->id,
                'original_journal_voucher_id' => $run->journal_voucher_id,
                'reversal_journal_voucher_id' => $reversalVoucher->id,
                'reason' => $reason,
                'reversed_by' => $userId,
                'reversed_at' => now(),
            ]);
        });
    }

    private function buildRunLines(ErpPayrollRun $run, $employees, Carbon $periodFrom, Carbon $periodTo, ErpPayrollPostingSetting $settings, array $data): array
    {
        $totals = [
            'gross_salary' => 0, 'salary_expense' => 0, 'allowances' => 0, 'deductions' => 0, 'tds' => 0,
            'pf' => 0, 'ssf_employee' => 0, 'ssf_employer' => 0, 'cit' => 0, 'advance_recovery' => 0,
            'loan_recovery' => 0, 'net_payable' => 0,
        ];
        $attendanceSummary = ['present_days' => 0, 'leave_days' => 0, 'unpaid_days' => 0, 'payable_days' => 0];
        $standardWorkdays = (int) ($data['standard_workdays'] ?? 26);
        $rates = $settings->statutory_rates ?? [];

        foreach ($employees as $employee) {
            $presentDays = AttendanceLog::where('employeeId', $employee->id)
                ->whereBetween('date', [$periodFrom->toDateString(), $periodTo->toDateString()])
                ->whereIn('status', ['Present', 'Late'])
                ->count();
            $leaveDays = $this->approvedLeaveDays($employee->id, $periodFrom, $periodTo);
            $payableDays = min($standardWorkdays, $presentDays + $leaveDays);
            $unpaidDays = max(0, $standardWorkdays - $payableDays);
            $prorationFactor = $standardWorkdays > 0 ? $payableDays / $standardWorkdays : 1;
            $basic = round((float) $employee->salaryBasic, 2);
            $proratedBasic = round($basic * $prorationFactor, 2);
            $allowances = round((float) $employee->salaryAllowances * $prorationFactor, 2);
            $gross = round($proratedBasic + $allowances, 2);
            $ssfEmployee = round($proratedBasic * ((float) ($rates['ssf_employee'] ?? 11) / 100), 2);
            $ssfEmployer = round($proratedBasic * ((float) ($rates['ssf_employer'] ?? 20) / 100), 2);
            $pf = round($proratedBasic * ((float) ($rates['pf'] ?? 0) / 100), 2);
            $cit = $gross > 0 ? min($this->citAmount($employee), max(0, $gross - $ssfEmployee - $pf)) : 0;
            $taxable = max(0, $gross - $ssfEmployee - $pf - $cit);
            $tds = round($taxable * $this->tdsRate($taxable), 2);
            $deductions = min($gross, round((float) $employee->salaryDeductions + $ssfEmployee + $pf + $cit + $tds, 2));
            $net = max(0, round($gross - $deductions, 2));

            $run->lines()->create([
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'department' => $employee->department,
                'designation' => $employee->designation,
                'pan' => $employee->pan,
                'ssf_number' => $employee->ssf,
                'basic_salary' => $basic,
                'prorated_basic' => $proratedBasic,
                'allowances' => $allowances,
                'gross_salary' => $gross,
                'deductions' => $deductions,
                'tds' => $tds,
                'pf' => $pf,
                'ssf_employee' => $ssfEmployee,
                'ssf_employer' => $ssfEmployer,
                'cit' => $cit,
                'advance_recovery' => 0,
                'loan_recovery' => 0,
                'net_payable' => $net,
                'present_days' => $presentDays,
                'leave_days' => $leaveDays,
                'unpaid_days' => $unpaidDays,
                'payable_days' => $payableDays,
                'component_breakdown' => ['taxable_income' => $taxable, 'proration_factor' => $prorationFactor],
            ]);

            foreach ($totals as $field => $value) {
                $totals[$field] += match ($field) {
                    'salary_expense' => $proratedBasic,
                    'gross_salary' => $gross,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'tds' => $tds,
                    'pf' => $pf,
                    'ssf_employee' => $ssfEmployee,
                    'ssf_employer' => $ssfEmployer,
                    'cit' => $cit,
                    'net_payable' => $net,
                    default => 0,
                };
            }

            $attendanceSummary['present_days'] += $presentDays;
            $attendanceSummary['leave_days'] += $leaveDays;
            $attendanceSummary['unpaid_days'] += $unpaidDays;
            $attendanceSummary['payable_days'] += $payableDays;
        }

        $totals['attendance_summary'] = $attendanceSummary;
        $totals['leave_summary'] = ['source' => 'approved HRM leave requests'];

        return array_map(fn ($value) => is_float($value) ? round($value, 2) : $value, $totals);
    }

    private function journalLines(ErpPayrollRun $run, ErpPayrollPostingSetting $settings): array
    {
        $lines = [
            ['account_id' => $settings->salary_expense_account_id, 'description' => 'Payroll basic salary expense', 'debit' => $run->salary_expense, 'credit' => 0],
            ['account_id' => $settings->allowance_expense_account_id ?: $settings->salary_expense_account_id, 'description' => 'Payroll allowance expense', 'debit' => $run->allowances, 'credit' => 0],
            ['account_id' => $settings->salary_payable_account_id, 'description' => 'Net salary payable to employees', 'debit' => 0, 'credit' => $run->net_payable],
        ];

        if ($settings->ssf_payable_account_id && round((float) $run->ssf_employer, 2) > 0) {
            $lines[] = ['account_id' => $settings->salary_expense_account_id, 'description' => 'Employer SSF contribution expense', 'debit' => round((float) $run->ssf_employer, 2), 'credit' => 0];
            $lines[] = ['account_id' => $settings->ssf_payable_account_id, 'description' => 'Employee and employer SSF payable', 'debit' => 0, 'credit' => round((float) ($run->ssf_employee + $run->ssf_employer), 2)];
        }

        foreach ([
            [$settings->tds_payable_account_id, $run->tds, 'Payroll TDS payable'],
            [$settings->pf_payable_account_id, $run->pf, 'Payroll PF payable'],
            [$settings->cit_payable_account_id, $run->cit, 'Payroll CIT payable'],
            [$settings->deduction_account_id, max(0, $run->deductions - $run->tds - $run->pf - $run->ssf_employee - $run->cit), 'Other payroll deductions'],
            [$settings->advance_account_id, $run->advance_recovery, 'Employee advance recovery'],
            [$settings->loan_recovery_account_id, $run->loan_recovery, 'Employee loan recovery'],
        ] as [$accountId, $amount, $description]) {
            if ($accountId && round((float) $amount, 2) > 0) {
                $lines[] = ['account_id' => $accountId, 'description' => $description, 'debit' => 0, 'credit' => round((float) $amount, 2)];
            }
        }

        return array_values(array_filter($lines, fn ($line) => round((float) ($line['debit'] + $line['credit']), 2) > 0));
    }

    private function approvedLeaveDays(string $employeeId, Carbon $periodFrom, Carbon $periodTo): int
    {
        return LeaveRequest::where('employeeId', $employeeId)
            ->where('status', 'Approved')
            ->whereBetween('startDate', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->get()
            ->sum(fn ($leave) => Carbon::parse($leave->startDate)->diffInDays(Carbon::parse($leave->endDate)) + 1);
    }

    private function citAmount(Employee $employee): float
    {
        if (! $employee->cit) {
            return 3500.0;
        }

        return (float) (preg_replace('/[^0-9.]/', '', $employee->cit) ?: 3500);
    }

    private function tdsRate(float $taxableIncome): float
    {
        if ($taxableIncome > 75000) {
            return 0.36;
        }
        if ($taxableIncome > 55000) {
            return 0.20;
        }
        if ($taxableIncome > 40000) {
            return 0.10;
        }

        return 0.01;
    }
}
