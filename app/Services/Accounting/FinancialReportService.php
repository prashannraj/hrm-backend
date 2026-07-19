<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function dashboard(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $trialBalance = $this->trialBalance($companyId, $fiscalYearId, $from, $to);
        $profitAndLoss = $this->profitAndLoss($companyId, $fiscalYearId, $from, $to);
        $balanceSheet = $this->balanceSheet($companyId, $fiscalYearId, $to);
        $cashFlow = $this->cashFlow($companyId, $fiscalYearId, $from, $to);

        return [
            'trial_debit' => round((float) $trialBalance->sum('debit'), 2),
            'trial_credit' => round((float) $trialBalance->sum('credit'), 2),
            'is_balanced' => round((float) $trialBalance->sum('debit'), 2) === round((float) $trialBalance->sum('credit'), 2),
            'income' => $profitAndLoss['total_income'],
            'expenses' => $profitAndLoss['total_expenses'],
            'net_profit' => $profitAndLoss['net_profit'],
            'assets' => $balanceSheet['total_assets'],
            'liabilities' => $balanceSheet['total_liabilities'],
            'equity' => $balanceSheet['total_equity'],
            'cash_net_change' => $cashFlow['net_cash_change'],
            'cash_closing_balance' => $cashFlow['closing_cash_balance'],
        ];
    }

    public function trialBalance(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): Collection
    {
        return $this->accountBalances($companyId, $fiscalYearId, $from, $to)
            ->map(function ($row) {
                $debit = round((float) $row->opening_debit + (float) $row->movement_debit, 2);
                $credit = round((float) $row->opening_credit + (float) $row->movement_credit, 2);

                return [
                    'id' => (int) $row->id,
                    'account_id' => (int) $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'type' => $row->type,
                    'opening_debit' => round((float) $row->opening_debit, 2),
                    'opening_credit' => round((float) $row->opening_credit, 2),
                    'movement_debit' => round((float) $row->movement_debit, 2),
                    'movement_credit' => round((float) $row->movement_credit, 2),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $this->signedBalance($row->type, $debit, $credit),
                ];
            })
            ->values();
    }

    public function generalLedger(int $companyId, int $fiscalYearId, ?int $accountId = null, ?string $from = null, ?string $to = null): array
    {
        $opening = $this->openingBalance($companyId, $fiscalYearId, $accountId);
        $lines = DB::table('erp_journal_lines')
            ->join('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->join('erp_accounts', 'erp_accounts.id', '=', 'erp_journal_lines.account_id')
            ->where('erp_journal_vouchers.company_id', $companyId)
            ->where('erp_journal_vouchers.fiscal_year_id', $fiscalYearId)
            ->where('erp_journal_vouchers.status', 'posted')
            ->when($accountId, fn ($query) => $query->where('erp_journal_lines.account_id', $accountId))
            ->when($from, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '<=', $to))
            ->orderBy('erp_journal_vouchers.voucher_date_ad')
            ->orderBy('erp_journal_vouchers.voucher_number')
            ->orderBy('erp_journal_lines.line_order')
            ->select(
                'erp_journal_lines.id',
                'erp_journal_lines.account_id',
                'erp_journal_lines.description',
                'erp_journal_lines.debit',
                'erp_journal_lines.credit',
                'erp_accounts.code as account_code',
                'erp_accounts.name as account_name',
                'erp_accounts.type as account_type',
                'erp_journal_vouchers.voucher_number',
                'erp_journal_vouchers.voucher_type',
                'erp_journal_vouchers.voucher_date_ad',
                'erp_journal_vouchers.narration'
            )
            ->get();

        $running = round($opening['debit'] - $opening['credit'], 2);
        $lines = $lines->map(function ($line) use (&$running) {
            $running = round($running + (float) $line->debit - (float) $line->credit, 2);
            $line->running_balance = $running;
            return $line;
        });

        return [
            'opening' => $opening,
            'lines' => $lines,
            'closing_balance' => $running,
        ];
    }

    public function profitAndLoss(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->trialBalance($companyId, $fiscalYearId, $from, $to);
        $income = $rows->where('type', 'income')->values();
        $expenses = $rows->where('type', 'expense')->values();
        $totalIncome = round((float) $income->sum('balance'), 2);
        $totalExpenses = round((float) $expenses->sum('balance'), 2);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => round($totalIncome - $totalExpenses, 2),
        ];
    }

    public function balanceSheet(int $companyId, int $fiscalYearId, ?string $asOf = null): array
    {
        $rows = $this->trialBalance($companyId, $fiscalYearId, null, $asOf);
        $assets = $rows->where('type', 'asset')->values();
        $liabilities = $rows->where('type', 'liability')->values();
        $equity = $rows->where('type', 'equity')->values();
        $profitAndLoss = $this->profitAndLoss($companyId, $fiscalYearId, null, $asOf);

        $totalAssets = round((float) $assets->sum('balance'), 2);
        $totalLiabilities = round((float) $liabilities->sum('balance'), 2);
        $totalEquity = round((float) $equity->sum('balance') + (float) $profitAndLoss['net_profit'], 2);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'current_period_profit' => $profitAndLoss['net_profit'],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
            'difference' => round($totalAssets - ($totalLiabilities + $totalEquity), 2),
        ];
    }

    public function cashFlow(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $cashAccountIds = DB::table('erp_accounts')
            ->where('company_id', $companyId)
            ->where(fn ($query) => $query->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->pluck('id');

        $opening = $this->openingBalance($companyId, $fiscalYearId, null, $cashAccountIds->all());
        $cashMovements = DB::table('erp_journal_lines')
            ->join('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->whereIn('erp_journal_lines.account_id', $cashAccountIds)
            ->where('erp_journal_vouchers.company_id', $companyId)
            ->where('erp_journal_vouchers.fiscal_year_id', $fiscalYearId)
            ->where('erp_journal_vouchers.status', 'posted')
            ->when($from, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '<=', $to))
            ->selectRaw('COALESCE(SUM(erp_journal_lines.debit), 0) as debit, COALESCE(SUM(erp_journal_lines.credit), 0) as credit')
            ->first();

        $netChange = round((float) $cashMovements->debit - (float) $cashMovements->credit, 2);
        $openingCash = round($opening['debit'] - $opening['credit'], 2);

        return [
            'opening_cash_balance' => $openingCash,
            'cash_inflows' => round((float) $cashMovements->debit, 2),
            'cash_outflows' => round((float) $cashMovements->credit, 2),
            'net_cash_change' => $netChange,
            'closing_cash_balance' => round($openingCash + $netChange, 2),
            'cash_accounts' => $cashAccountIds->count(),
        ];
    }

    private function accountBalances(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): Collection
    {
        $movementSubquery = DB::table('erp_journal_lines')
            ->join('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->where('erp_journal_vouchers.company_id', $companyId)
            ->where('erp_journal_vouchers.fiscal_year_id', $fiscalYearId)
            ->where('erp_journal_vouchers.status', 'posted')
            ->when($from, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('erp_journal_vouchers.voucher_date_ad', '<=', $to))
            ->groupBy('erp_journal_lines.account_id')
            ->selectRaw('erp_journal_lines.account_id, COALESCE(SUM(erp_journal_lines.debit), 0) as debit, COALESCE(SUM(erp_journal_lines.credit), 0) as credit');

        return DB::table('erp_accounts')
            ->leftJoin('erp_account_opening_balances', function ($join) use ($companyId, $fiscalYearId) {
                $join->on('erp_account_opening_balances.account_id', '=', 'erp_accounts.id')
                    ->where('erp_account_opening_balances.company_id', '=', $companyId)
                    ->where('erp_account_opening_balances.fiscal_year_id', '=', $fiscalYearId);
            })
            ->leftJoinSub($movementSubquery, 'movements', fn ($join) => $join->on('movements.account_id', '=', 'erp_accounts.id'))
            ->where('erp_accounts.company_id', $companyId)
            ->select(
                'erp_accounts.id',
                'erp_accounts.code',
                'erp_accounts.name',
                'erp_accounts.type',
                DB::raw('COALESCE(erp_account_opening_balances.debit, 0) as opening_debit'),
                DB::raw('COALESCE(erp_account_opening_balances.credit, 0) as opening_credit'),
                DB::raw('COALESCE(movements.debit, 0) as movement_debit'),
                DB::raw('COALESCE(movements.credit, 0) as movement_credit')
            )
            ->orderBy('erp_accounts.code')
            ->get();
    }

    private function openingBalance(int $companyId, int $fiscalYearId, ?int $accountId = null, array $accountIds = []): array
    {
        $query = DB::table('erp_account_opening_balances')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->when($accountIds, fn ($query) => $query->whereIn('account_id', $accountIds));

        return [
            'debit' => round((float) $query->sum('debit'), 2),
            'credit' => round((float) $query->sum('credit'), 2),
        ];
    }

    private function signedBalance(string $type, float $debit, float $credit): float
    {
        return in_array($type, ['asset', 'expense'], true)
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }
}
