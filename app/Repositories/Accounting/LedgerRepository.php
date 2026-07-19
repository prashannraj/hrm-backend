<?php

namespace App\Repositories\Accounting;

use App\Models\ErpAccountOpeningBalance;
use App\Models\ErpJournalLine;
use App\Models\ErpJournalVoucher;
use Illuminate\Support\Facades\DB;

class LedgerRepository
{
    public function openingBalances(int $companyId, int $fiscalYearId, ?int $accountId = null)
    {
        return ErpAccountOpeningBalance::query()
            ->with('account')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->orderBy('account_id')
            ->get();
    }

    public function postedLines(int $companyId, int $fiscalYearId, ?int $accountId = null)
    {
        return ErpJournalLine::query()
            ->select('erp_journal_lines.*')
            ->with(['account', 'voucher'])
            ->join('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->where('erp_journal_vouchers.company_id', $companyId)
            ->where('erp_journal_vouchers.fiscal_year_id', $fiscalYearId)
            ->where('erp_journal_vouchers.status', 'posted')
            ->when($accountId, fn ($query) => $query->where('erp_journal_lines.account_id', $accountId))
            ->orderBy('erp_journal_vouchers.voucher_date_ad')
            ->orderBy('erp_journal_vouchers.voucher_number')
            ->orderBy('erp_journal_lines.line_order')
            ->get();
    }

    public function trialBalance(int $companyId, int $fiscalYearId)
    {
        return DB::table('erp_accounts')
            ->leftJoin('erp_account_opening_balances', function ($join) use ($companyId, $fiscalYearId) {
                $join->on('erp_account_opening_balances.account_id', '=', 'erp_accounts.id')
                    ->where('erp_account_opening_balances.company_id', '=', $companyId)
                    ->where('erp_account_opening_balances.fiscal_year_id', '=', $fiscalYearId);
            })
            ->leftJoin('erp_journal_lines', 'erp_journal_lines.account_id', '=', 'erp_accounts.id')
            ->leftJoin('erp_journal_vouchers', function ($join) use ($companyId, $fiscalYearId) {
                $join->on('erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
                    ->where('erp_journal_vouchers.company_id', '=', $companyId)
                    ->where('erp_journal_vouchers.fiscal_year_id', '=', $fiscalYearId)
                    ->where('erp_journal_vouchers.status', '=', 'posted');
            })
            ->where('erp_accounts.company_id', $companyId)
            ->select(
                'erp_accounts.id',
                'erp_accounts.code',
                'erp_accounts.name',
                'erp_accounts.type',
                DB::raw('COALESCE(erp_account_opening_balances.debit, 0) + COALESCE(SUM(erp_journal_lines.debit), 0) as debit'),
                DB::raw('COALESCE(erp_account_opening_balances.credit, 0) + COALESCE(SUM(erp_journal_lines.credit), 0) as credit')
            )
            ->groupBy('erp_accounts.id', 'erp_accounts.code', 'erp_accounts.name', 'erp_accounts.type', 'erp_account_opening_balances.debit', 'erp_account_opening_balances.credit')
            ->orderBy('erp_accounts.code')
            ->get();
    }
}
