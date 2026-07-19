<?php

namespace App\Http\Controllers;

use App\Models\ErpAccount;
use App\Models\ErpBankAccount;
use App\Models\ErpBankReconciliation;
use App\Models\ErpBankStatement;
use App\Models\ErpPettyCashFund;
use App\Models\ErpPettyCashTransaction;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Banking\BankingReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankingController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly BankingReconciliationService $bankingService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json($this->bankingService->dashboard($context['company']->id));
    }

    public function masters()
    {
        $context = $this->context();

        return response()->json([
            'accounts' => ErpAccount::where('company_id', $context['company']->id)
                ->where(fn ($query) => $query->where('is_bank_account', true)->orWhere('is_cash_account', true))
                ->orderBy('code')
                ->get(),
            'expense_accounts' => ErpAccount::where('company_id', $context['company']->id)
                ->whereIn('type', ['asset', 'expense', 'liability'])
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function bankAccounts()
    {
        $context = $this->context();

        return response()->json(ErpBankAccount::with('account')
            ->where('company_id', $context['company']->id)
            ->orderByDesc('is_active')
            ->orderBy('bank_name')
            ->get());
    }

    public function storeBankAccount(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_cash_account' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->bankingService->createBankAccount(array_merge($data, [
            'company_id' => $context['company']->id,
        ]))->load('account'), 201);
    }

    public function statements()
    {
        $context = $this->context();

        return response()->json(ErpBankStatement::with(['bankAccount', 'lines'])
            ->where('company_id', $context['company']->id)
            ->latest('statement_date')
            ->limit(30)
            ->get());
    }

    public function storeStatement(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('erp_bank_accounts', 'id')->where('company_id', $context['company']->id)],
            'statement_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::in(['draft', 'imported', 'locked'])],
            'remarks' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.txn_date' => ['required_with:lines', 'date'],
            'lines.*.reference' => ['nullable', 'string', 'max:120'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json($this->bankingService->createStatement(array_merge($data, [
            'company_id' => $context['company']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ]), $request->user()?->id), 201);
    }

    public function book(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'bank_account_id' => ['nullable', Rule::exists('erp_bank_accounts', 'id')->where('company_id', $context['company']->id)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->json($this->bankingService->book(
            $context['company']->id,
            $data['bank_account_id'] ?? null,
            $data['from'] ?? null,
            $data['to'] ?? null
        ));
    }

    public function reconcile(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('erp_bank_accounts', 'id')->where('company_id', $context['company']->id)],
            'bank_statement_id' => ['required', Rule::exists('erp_bank_statements', 'id')->where('company_id', $context['company']->id)],
            'reconciled_on' => ['required', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'locked'])],
        ]);

        return response()->json($this->bankingService->reconcile(array_merge($data, [
            'company_id' => $context['company']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ]), $request->user()?->id), 201);
    }

    public function reconciliations()
    {
        $context = $this->context();

        return response()->json(ErpBankReconciliation::with('bankAccount')
            ->where('company_id', $context['company']->id)
            ->latest('reconciled_on')
            ->limit(30)
            ->get());
    }

    public function pettyCashFunds()
    {
        $context = $this->context();

        return response()->json(ErpPettyCashFund::with(['account', 'transactions'])
            ->where('company_id', $context['company']->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get());
    }

    public function storePettyCashFund(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'custodian_id' => ['nullable', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'current_balance' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->bankingService->createPettyCashFund(array_merge($data, [
            'company_id' => $context['company']->id,
        ]))->load('account'), 201);
    }

    public function storePettyCashTransaction(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'petty_cash_fund_id' => ['required', Rule::exists('erp_petty_cash_funds', 'id')->where('company_id', $context['company']->id)],
            'txn_date' => ['required', 'date'],
            'txn_date_bs' => ['nullable', 'string', 'max:20'],
            'txn_type' => ['required', Rule::in(['top_up', 'issue', 'expense', 'settlement'])],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'offset_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
        ]);

        return response()->json($this->bankingService->pettyCashTransaction($data, $this->contextIds(), $request->user()?->id), 201);
    }

    public function pettyCashTransactions()
    {
        return response()->json(ErpPettyCashTransaction::with(['fund', 'fund.account'])
            ->latest('txn_date')
            ->limit(50)
            ->get());
    }

    private function context(): array
    {
        $company = $this->setupRepository->defaultCompany();
        abort_if(!$company, 422, 'ERP company is not configured. Run ERP accounting seeders.');

        $settings = $this->setupRepository->settingsForCompany($company->id);
        abort_if(!$settings || !$settings->defaultBranch || !$settings->currentFiscalYear, 422, 'ERP accounting settings are incomplete.');

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
