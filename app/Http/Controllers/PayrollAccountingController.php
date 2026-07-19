<?php

namespace App\Http\Controllers;

use App\Models\ErpAccount;
use App\Models\ErpPayrollPostingSetting;
use App\Models\ErpPayrollRun;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Payroll\PayrollAccountingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollAccountingController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly PayrollAccountingService $payrollService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json($this->payrollService->dashboard($context['company']->id));
    }

    public function masters()
    {
        $context = $this->context();

        return response()->json([
            'accounts' => ErpAccount::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'expense_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'expense')->orderBy('code')->get(),
            'liability_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'liability')->orderBy('code')->get(),
            'asset_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'asset')->orderBy('code')->get(),
            'settings' => ErpPayrollPostingSetting::where('company_id', $context['company']->id)->first(),
        ]);
    }

    public function settings()
    {
        $context = $this->context();

        return response()->json(ErpPayrollPostingSetting::where('company_id', $context['company']->id)->first());
    }

    public function saveSettings(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'salary_expense_account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'salary_payable_account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'allowance_expense_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'deduction_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'tds_payable_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'pf_payable_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'ssf_payable_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'cit_payable_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'advance_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'loan_recovery_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'statutory_rates' => ['nullable', 'array'],
        ]);

        return response()->json($this->payrollService->saveSettings(array_merge($data, [
            'company_id' => $context['company']->id,
        ])), 201);
    }

    public function runs()
    {
        $context = $this->context();

        return response()->json(ErpPayrollRun::with(['lines', 'journalVoucher.lines.account'])
            ->where('company_id', $context['company']->id)
            ->latest()
            ->limit(50)
            ->get());
    }

    public function generateRun(Request $request)
    {
        $data = $request->validate([
            'payroll_number' => ['nullable', 'string', 'max:50'],
            'period_month' => ['nullable', 'date_format:Y-m'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'posting_date_ad' => ['nullable', 'date'],
            'posting_date_bs' => ['nullable', 'string', 'max:20'],
            'standard_workdays' => ['nullable', 'integer', 'min:1', 'max:31'],
            'post_now' => ['boolean'],
        ]);

        return response()->json($this->payrollService->generateRun($data, $this->contextIds(), $request->user()?->id), 201);
    }

    public function postRun(Request $request, ErpPayrollRun $run)
    {
        return response()->json($this->payrollService->postRun($run, $this->contextIds(), $request->user()?->id));
    }

    public function lockRun(ErpPayrollRun $run)
    {
        return response()->json($this->payrollService->lockRun($run));
    }

    public function reverseRun(Request $request, ErpPayrollRun $run)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json($this->payrollService->reverseRun($run, $request->user()?->id, $data['reason'] ?? ''), 201);
    }

    private function context(): array
    {
        $company = $this->setupRepository->defaultCompany();
        $settings = $this->setupRepository->settingsForCompany($company->id);

        return [
            'company' => $company,
            'branch' => $settings->defaultBranch,
            'fiscalYear' => $settings->currentFiscalYear,
        ];
    }

    private function contextIds(): array
    {
        $context = $this->context();

        return [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ];
    }
}
