<?php

namespace App\Services\Accounting;

use App\Models\ErpAuditEvent;
use App\Models\ErpFiscalYear;
use App\Models\ErpJournalVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ErpIntegrityService
{
    public function __construct(private readonly FinancialReportService $reportService)
    {
    }

    public function dashboard(int $companyId, int $fiscalYearId): array
    {
        $trialBalance = $this->reportService->trialBalance($companyId, $fiscalYearId);
        $trialDebit = round((float) $trialBalance->sum('debit'), 2);
        $trialCredit = round((float) $trialBalance->sum('credit'), 2);
        $unbalancedVouchers = $this->unbalancedVoucherCount($companyId, $fiscalYearId);
        $draftVouchers = ErpJournalVoucher::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'draft')
            ->count();
        $outsideFiscalYear = $this->outsideFiscalYearVoucherCount($companyId, $fiscalYearId);
        $orphanLines = DB::table('erp_journal_lines')
            ->leftJoin('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->whereNull('erp_journal_vouchers.id')
            ->count();

        return [
            'trial_debit' => $trialDebit,
            'trial_credit' => $trialCredit,
            'trial_difference' => round($trialDebit - $trialCredit, 2),
            'unbalanced_vouchers' => $unbalancedVouchers,
            'draft_vouchers' => $draftVouchers,
            'outside_fiscal_year_vouchers' => $outsideFiscalYear,
            'orphan_journal_lines' => $orphanLines,
            'audit_events' => ErpAuditEvent::where('company_id', $companyId)->count(),
            'last_audit_at' => ErpAuditEvent::where('company_id', $companyId)->latest()->value('created_at'),
            'is_healthy' => $trialDebit === $trialCredit && $unbalancedVouchers === 0 && $outsideFiscalYear === 0 && $orphanLines === 0,
        ];
    }

    public function closeFiscalYear(ErpFiscalYear $fiscalYear): ErpFiscalYear
    {
        $dashboard = $this->dashboard($fiscalYear->company_id, $fiscalYear->id);

        if (! $dashboard['is_healthy']) {
            throw ValidationException::withMessages(['fiscal_year_id' => 'Fiscal year cannot be closed until ERP integrity checks are healthy.']);
        }

        if ($dashboard['draft_vouchers'] > 0) {
            throw ValidationException::withMessages(['fiscal_year_id' => 'Fiscal year cannot be closed while draft vouchers exist.']);
        }

        $fiscalYear->update(['is_closed' => true]);

        return $fiscalYear->fresh();
    }

    public function reopenFiscalYear(ErpFiscalYear $fiscalYear): ErpFiscalYear
    {
        $fiscalYear->update(['is_closed' => false]);

        return $fiscalYear->fresh();
    }

    private function unbalancedVoucherCount(int $companyId, int $fiscalYearId): int
    {
        return ErpJournalVoucher::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereRaw('ROUND(total_debit, 2) <> ROUND(total_credit, 2)')
            ->count();
    }

    private function outsideFiscalYearVoucherCount(int $companyId, int $fiscalYearId): int
    {
        $fiscalYear = ErpFiscalYear::findOrFail($fiscalYearId);

        return ErpJournalVoucher::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where(function ($query) use ($fiscalYear) {
                $query->whereDate('voucher_date_ad', '<', $fiscalYear->starts_on_ad)
                    ->orWhereDate('voucher_date_ad', '>', $fiscalYear->ends_on_ad);
            })
            ->count();
    }
}
