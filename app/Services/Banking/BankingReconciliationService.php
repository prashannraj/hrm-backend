<?php

namespace App\Services\Banking;

use App\Models\ErpAccount;
use App\Models\ErpBankAccount;
use App\Models\ErpBankReconciliation;
use App\Models\ErpBankStatement;
use App\Models\ErpJournalLine;
use App\Models\ErpPettyCashFund;
use App\Models\ErpPettyCashTransaction;
use App\Services\Accounting\JournalVoucherService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankingReconciliationService
{
    public function __construct(private readonly JournalVoucherService $journalVoucherService)
    {
    }

    public function dashboard(int $companyId): array
    {
        $bankAccounts = ErpBankAccount::where('company_id', $companyId)->where('is_active', true)->get();
        $pettyCashFunds = ErpPettyCashFund::where('company_id', $companyId)->where('is_active', true)->get();
        $latestReconciliations = ErpBankReconciliation::with('bankAccount')
            ->where('company_id', $companyId)
            ->latest('reconciled_on')
            ->limit(10)
            ->get();

        return [
            'bank_accounts' => $bankAccounts->count(),
            'cash_accounts' => $bankAccounts->where('is_cash_account', true)->count(),
            'bank_balance' => round($bankAccounts->where('is_cash_account', false)->sum(fn ($account) => $this->bookBalance($account)), 2),
            'cash_balance' => round($bankAccounts->where('is_cash_account', true)->sum(fn ($account) => $this->bookBalance($account)), 2),
            'petty_cash_balance' => round($pettyCashFunds->sum('current_balance'), 2),
            'unmatched_statement_lines' => DB::table('erp_bank_statement_lines')
                ->join('erp_bank_statements', 'erp_bank_statement_lines.bank_statement_id', '=', 'erp_bank_statements.id')
                ->where('erp_bank_statements.company_id', $companyId)
                ->where('erp_bank_statement_lines.is_matched', false)
                ->count(),
            'latest_reconciliations' => $latestReconciliations,
        ];
    }

    public function createBankAccount(array $data): ErpBankAccount
    {
        $account = ErpAccount::where('company_id', $data['company_id'])->findOrFail($data['account_id']);

        if (!$account->is_bank_account && !$account->is_cash_account) {
            throw ValidationException::withMessages(['account_id' => 'Linked ledger must be marked as bank or cash account.']);
        }

        return ErpBankAccount::create(array_merge($data, [
            'is_cash_account' => (bool) ($data['is_cash_account'] ?? $account->is_cash_account),
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    public function createStatement(array $data, ?int $userId = null): ErpBankStatement
    {
        return DB::transaction(function () use ($data, $userId) {
            $statement = ErpBankStatement::create([
                'company_id' => $data['company_id'],
                'bank_account_id' => $data['bank_account_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'statement_date' => $data['statement_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'closing_balance' => $data['closing_balance'] ?? 0,
                'status' => $data['status'] ?? 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                $statement->lines()->create([
                    'txn_date' => $line['txn_date'],
                    'reference' => $line['reference'] ?? null,
                    'description' => $line['description'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'amount' => $credit - $debit,
                    'is_matched' => false,
                ]);
            }

            return $statement->load('lines');
        });
    }

    public function book(int $companyId, ?int $bankAccountId = null, ?string $from = null, ?string $to = null)
    {
        return ErpJournalLine::with(['voucher', 'account'])
            ->whereHas('voucher', function ($query) use ($companyId, $from, $to) {
                $query->where('company_id', $companyId)->where('status', 'posted')
                    ->when($from, fn ($q) => $q->whereDate('voucher_date_ad', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('voucher_date_ad', '<=', $to));
            })
            ->whereHas('account', function ($query) use ($bankAccountId) {
                $query->when($bankAccountId, fn ($q) => $q->whereIn('id', ErpBankAccount::where('id', $bankAccountId)->pluck('account_id')))
                    ->when(!$bankAccountId, fn ($q) => $q->where(fn ($inner) => $inner->where('is_bank_account', true)->orWhere('is_cash_account', true)));
            })
            ->latest('id')
            ->get();
    }

    public function reconcile(array $data, ?int $userId = null): ErpBankReconciliation
    {
        return DB::transaction(function () use ($data, $userId) {
            $bankAccount = ErpBankAccount::findOrFail($data['bank_account_id']);
            $statement = ErpBankStatement::with('lines')->findOrFail($data['bank_statement_id']);
            $bookLines = $this->book($data['company_id'], $bankAccount->id, $data['from_date'] ?? null, $data['to_date'] ?? null);
            $matched = [];

            foreach ($statement->lines->where('is_matched', false) as $statementLine) {
                $match = $bookLines->first(function ($line) use ($statementLine, $matched) {
                    $net = round((float) $line->debit - (float) $line->credit, 2);
                    $statementAmount = round((float) $statementLine->amount, 2);
                    $alreadyMatched = in_array($line->id, array_column($matched, 'journal_line_id'), true);

                    return !$alreadyMatched && $net === $statementAmount;
                });

                if ($match) {
                    $statementLine->update([
                        'is_matched' => true,
                        'matched_voucher_line_id' => $match->id,
                    ]);
                    $matched[] = [
                        'statement_line_id' => $statementLine->id,
                        'journal_line_id' => $match->id,
                        'amount' => $statementLine->amount,
                    ];
                }
            }

            $statementBalance = (float) $statement->closing_balance;
            $bookBalance = $this->bookBalance($bankAccount, $data['to_date'] ?? $statement->statement_date->format('Y-m-d'));

            return ErpBankReconciliation::create([
                'company_id' => $data['company_id'],
                'bank_account_id' => $bankAccount->id,
                'fiscal_year_id' => $data['fiscal_year_id'],
                'reconciled_on' => $data['reconciled_on'],
                'statement_balance' => $statementBalance,
                'book_balance' => $bookBalance,
                'difference' => round($statementBalance - $bookBalance, 2),
                'status' => $data['status'] ?? 'locked',
                'matched_lines' => $matched,
                'created_by' => $userId,
            ])->load('bankAccount');
        });
    }

    public function createPettyCashFund(array $data): ErpPettyCashFund
    {
        return ErpPettyCashFund::create(array_merge($data, [
            'current_balance' => $data['current_balance'] ?? $data['opening_balance'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    public function pettyCashTransaction(array $data, array $context, ?int $userId = null): ErpPettyCashTransaction
    {
        return DB::transaction(function () use ($data, $context, $userId) {
            $fund = ErpPettyCashFund::with('account')->findOrFail($data['petty_cash_fund_id']);
            $amount = (float) $data['amount'];
            $type = $data['txn_type'];
            $signedAmount = in_array($type, ['top_up', 'issue'], true) ? $amount : -$amount;

            if ($fund->current_balance + $signedAmount < 0) {
                throw ValidationException::withMessages(['amount' => 'Petty cash fund does not have enough balance.']);
            }

            $voucher = null;
            if (!empty($data['offset_account_id']) && $fund->account_id) {
                $voucher = $this->journalVoucherService->create($this->pettyCashVoucherPayload($fund, $data, $context), $userId);
            }

            $fund->increment('current_balance', $signedAmount);

            return ErpPettyCashTransaction::create([
                'petty_cash_fund_id' => $fund->id,
                'txn_date' => $data['txn_date'],
                'txn_type' => $type,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'amount' => $amount,
                'journal_voucher_id' => $voucher?->id,
                'created_by' => $userId,
            ])->load('fund');
        });
    }

    private function bookBalance(ErpBankAccount $bankAccount, ?string $to = null): float
    {
        if (!$bankAccount->account_id) {
            return (float) $bankAccount->opening_balance;
        }

        $lines = ErpJournalLine::where('account_id', $bankAccount->account_id)
            ->whereHas('voucher', function ($query) use ($bankAccount, $to) {
                $query->where('company_id', $bankAccount->company_id)->where('status', 'posted')
                    ->when($to, fn ($q) => $q->whereDate('voucher_date_ad', '<=', $to));
            })
            ->get();

        return round((float) $bankAccount->opening_balance + $lines->sum('debit') - $lines->sum('credit'), 2);
    }

    private function pettyCashVoucherPayload(ErpPettyCashFund $fund, array $data, array $context): array
    {
        $amount = (float) $data['amount'];
        $cashLine = ['account_id' => $fund->account_id, 'debit' => 0, 'credit' => 0, 'description' => $data['description'] ?? 'Petty cash'];
        $offsetLine = ['account_id' => $data['offset_account_id'], 'debit' => 0, 'credit' => 0, 'description' => $data['description'] ?? 'Petty cash'];

        if (in_array($data['txn_type'], ['top_up', 'issue'], true)) {
            $cashLine['debit'] = $amount;
            $offsetLine['credit'] = $amount;
        } else {
            $cashLine['credit'] = $amount;
            $offsetLine['debit'] = $amount;
        }

        return [
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'fiscal_year_id' => $context['fiscal_year_id'],
            'voucher_type' => 'payment',
            'voucher_date_ad' => $data['txn_date'],
            'voucher_date_bs' => $data['txn_date_bs'] ?? null,
            'narration' => $data['description'] ?? 'Petty cash transaction',
            'post_now' => true,
            'lines' => [$cashLine, $offsetLine],
        ];
    }
}
