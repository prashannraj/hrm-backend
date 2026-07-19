<?php

namespace App\Http\Controllers;

use App\Models\ErpAccount;
use App\Models\ErpAccountOpeningBalance;
use App\Models\ErpJournalVoucher;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Repositories\Accounting\LedgerRepository;
use App\Services\Accounting\JournalVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly LedgerRepository $ledgerRepository,
        private readonly JournalVoucherService $journalVoucherService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();
        $trialBalance = $this->ledgerRepository->trialBalance($context['company']->id, $context['fiscalYear']->id);

        $totals = $trialBalance->reduce(function ($carry, $row) {
            $carry['debit'] += (float) $row->debit;
            $carry['credit'] += (float) $row->credit;
            if ($row->type === 'asset') {
                $carry['assets'] += (float) $row->debit - (float) $row->credit;
            }
            if ($row->type === 'liability') {
                $carry['liabilities'] += (float) $row->credit - (float) $row->debit;
            }
            if ($row->type === 'income') {
                $carry['income'] += (float) $row->credit - (float) $row->debit;
            }
            if ($row->type === 'expense') {
                $carry['expenses'] += (float) $row->debit - (float) $row->credit;
            }
            return $carry;
        }, ['debit' => 0, 'credit' => 0, 'assets' => 0, 'liabilities' => 0, 'income' => 0, 'expenses' => 0]);

        return response()->json([
            'company' => $context['company'],
            'branch' => $context['branch'],
            'fiscalYear' => $context['fiscalYear'],
            'totals' => $totals,
            'unpostedVouchers' => ErpJournalVoucher::where('company_id', $context['company']->id)->where('status', 'draft')->count(),
        ]);
    }

    public function chartOfAccounts()
    {
        $context = $this->context();

        return response()->json([
            'groups' => $this->setupRepository->accountGroups($context['company']->id),
            'accounts' => $this->setupRepository->accounts($context['company']->id),
        ]);
    }

    public function storeAccount(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'account_group_id' => ['required', 'exists:erp_account_groups,id'],
            'parent_id' => ['nullable', 'exists:erp_accounts,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_accounts')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'normal_balance' => ['required', Rule::in(['debit', 'credit'])],
            'is_cash_account' => ['boolean'],
            'is_bank_account' => ['boolean'],
            'is_tax_account' => ['boolean'],
            'pan' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
        ]);

        $account = ErpAccount::create(array_merge($data, [
            'company_id' => $context['company']->id,
            'currency_code' => $context['company']->base_currency_code,
            'is_active' => true,
        ]));

        return response()->json($account->load('group'), 201);
    }

    public function openingBalances(Request $request)
    {
        $context = $this->context();
        $accountId = $request->integer('account_id') ?: null;

        return response()->json($this->ledgerRepository->openingBalances($context['company']->id, $context['fiscalYear']->id, $accountId));
    }

    public function storeOpeningBalance(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'account_id' => ['required', 'exists:erp_accounts,id'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        if (((float) ($data['debit'] ?? 0)) > 0 && ((float) ($data['credit'] ?? 0)) > 0) {
            return response()->json(['message' => 'Opening balance cannot have both debit and credit.'], 422);
        }

        $opening = ErpAccountOpeningBalance::updateOrCreate(
            [
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
                'fiscal_year_id' => $context['fiscalYear']->id,
                'account_id' => $data['account_id'],
            ],
            [
                'debit' => $data['debit'] ?? 0,
                'credit' => $data['credit'] ?? 0,
                'remarks' => $data['remarks'] ?? null,
            ]
        );

        return response()->json($opening->load('account'), 201);
    }

    public function journals()
    {
        $context = $this->context();

        return response()->json(ErpJournalVoucher::with(['lines.account', 'branch', 'fiscalYear'])
            ->where('company_id', $context['company']->id)
            ->latest('voucher_date_ad')
            ->get());
    }

    public function storeJournal(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'voucher_date_ad' => ['required', 'date'],
            'voucher_date_bs' => ['nullable', 'string', 'max:20'],
            'narration' => ['nullable', 'string'],
            'post_now' => ['boolean'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:erp_accounts,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $voucher = $this->journalVoucherService->create(array_merge($data, [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
            'voucher_type' => 'journal',
        ]), $request->user()?->id);

        return response()->json($voucher, 201);
    }

    public function postJournal(Request $request, ErpJournalVoucher $voucher)
    {
        $posted = $this->journalVoucherService->post($voucher, $request->user()?->id);

        return response()->json($posted);
    }

    public function ledger(Request $request)
    {
        $context = $this->context();
        $accountId = $request->integer('account_id') ?: null;

        return response()->json([
            'openingBalances' => $this->ledgerRepository->openingBalances($context['company']->id, $context['fiscalYear']->id, $accountId),
            'lines' => $this->ledgerRepository->postedLines($context['company']->id, $context['fiscalYear']->id, $accountId),
        ]);
    }

    public function trialBalance()
    {
        $context = $this->context();

        return response()->json($this->ledgerRepository->trialBalance($context['company']->id, $context['fiscalYear']->id));
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
}
