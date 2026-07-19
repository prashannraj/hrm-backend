<?php

namespace App\Services\Accounting;

use App\Models\ErpFiscalYear;
use App\Models\ErpJournalVoucher;
use App\Models\ErpCommercialDocument;
use App\Models\ErpStockValuationLayer;
use App\Models\ErpAccount;
use Illuminate\Support\Facades\DB;

class ErpIntegrityCheckService
{
    public function runAllChecks(int $companyId, int $fiscalYearId): array
    {
        return [
            'unbalanced_journals' => $this->checkUnbalancedJournals($companyId, $fiscalYearId),
            'invoice_gaps' => $this->checkInvoiceGaps($companyId, $fiscalYearId),
            'duplicate_numbers' => $this->checkDuplicateNumbers($companyId, $fiscalYearId),
            'negative_stock' => $this->checkNegativeStock($companyId, $fiscalYearId),
            'missing_tax_ledgers' => $this->checkMissingTaxLedgers($companyId),
            'orphan_ledger_lines' => $this->checkOrphanLedgerLines($companyId),
            'orphan_stock_lines' => $this->checkOrphanStockLines($companyId),
            'is_healthy' => $this->isSystemHealthy($companyId, $fiscalYearId),
        ];
    }

    public function checkUnbalancedJournals(int $companyId, int $fiscalYearId): array
    {
        $vouchers = ErpJournalVoucher::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'posted')
            ->whereRaw('ROUND(total_debit, 2) <> ROUND(total_credit, 2)')
            ->select('id', 'voucher_number', 'voucher_type', 'total_debit', 'total_credit')
            ->get();

        return [
            'count' => $vouchers->count(),
            'vouchers' => $vouchers->map(fn ($v) => [
                'id' => $v->id,
                'voucher_number' => $v->voucher_number,
                'voucher_type' => $v->voucher_type,
                'debit' => $v->total_debit,
                'credit' => $v->total_credit,
                'difference' => round($v->total_debit - $v->total_credit, 2),
            ]),
        ];
    }

    public function checkInvoiceGaps(int $companyId, int $fiscalYearId): array
    {
        $gaps = [];
        $profiles = DB::table('erp_billing_profiles')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get();

        foreach ($profiles as $profile) {
            $usedNumbers = DB::table('erp_commercial_documents')
                ->where('company_id', $companyId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('document_type', $profile->profile_type)
                ->pluck('document_number')
                ->toArray();

            $expected = $profile->next_number;
            for ($i = 1; $i < $expected; $i++) {
                $expectedNumber = $profile->prefix . str_pad((string) $i, $profile->padding, '0', STR_PAD_LEFT);
                if (!in_array($expectedNumber, $usedNumbers)) {
                    $gaps[] = [
                        'profile_type' => $profile->profile_type,
                        'missing_number' => $expectedNumber,
                    ];
                }
            }
        }

        return [
            'count' => count($gaps),
            'gaps' => $gaps,
        ];
    }

    public function checkDuplicateNumbers(int $companyId, int $fiscalYearId): array
    {
        $duplicates = DB::table('erp_commercial_documents')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->select('document_number', DB::raw('COUNT(*) as count'))
            ->get();

        return [
            'count' => $duplicates->count(),
            'duplicates' => $duplicates->map(fn ($d) => [
                'document_number' => $d->document_number,
                'count' => $d->count,
            ]),
        ];
    }

    public function checkNegativeStock(int $companyId, int $fiscalYearId): array
    {
        $negativeStock = ErpStockValuationLayer::where('company_id', $companyId)
            ->where('remaining_quantity', '<', 0)
            ->select('item_id', 'warehouse_id', 'remaining_quantity')
            ->get();

        return [
            'count' => $negativeStock->count(),
            'items' => $negativeStock->map(fn ($s) => [
                'item_id' => $s->item_id,
                'warehouse_id' => $s->warehouse_id,
                'quantity' => $s->remaining_quantity,
            ]),
        ];
    }

    public function checkMissingTaxLedgers(int $companyId): array
    {
        $taxAccounts = ErpAccount::where('company_id', $companyId)
            ->where('is_tax_account', true)
            ->pluck('id');

        $missingTaxLedgers = DB::table('erp_commercial_documents')
            ->where('company_id', $companyId)
            ->where('vat_total', '>', 0)
            ->whereNotIn('id', function ($query) use ($taxAccounts) {
                $query->select('journal_voucher_id')
                    ->from('erp_journal_vouchers')
                    ->whereIn('id', function ($q) use ($taxAccounts) {
                        $q->select('journal_voucher_id')
                            ->from('erp_journal_lines')
                            ->whereIn('account_id', $taxAccounts);
                    });
            })
            ->select('id', 'document_number', 'vat_total')
            ->get();

        return [
            'count' => $missingTaxLedgers->count(),
            'documents' => $missingTaxLedgers->map(fn ($d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'vat_total' => $d->vat_total,
            ]),
        ];
    }

    public function checkOrphanLedgerLines(int $companyId): array
    {
        $orphans = DB::table('erp_journal_lines')
            ->leftJoin('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
            ->where('erp_journal_vouchers.company_id', $companyId)
            ->whereNull('erp_journal_vouchers.id')
            ->count();

        return [
            'count' => $orphans,
        ];
    }

    public function checkOrphanStockLines(int $companyId): array
    {
        $orphans = DB::table('erp_stock_movement_lines')
            ->leftJoin('erp_stock_movements', 'erp_stock_movements.id', '=', 'erp_stock_movement_lines.stock_movement_id')
            ->where('erp_stock_movements.company_id', $companyId)
            ->whereNull('erp_stock_movements.id')
            ->count();

        return [
            'count' => $orphans,
        ];
    }

    public function isSystemHealthy(int $companyId, int $fiscalYearId): bool
    {
        $checks = $this->runAllChecks($companyId, $fiscalYearId);
        
        return $checks['unbalanced_journals']['count'] === 0
            && $checks['invoice_gaps']['count'] === 0
            && $checks['duplicate_numbers']['count'] === 0
            && $checks['negative_stock']['count'] === 0
            && $checks['missing_tax_ledgers']['count'] === 0
            && $checks['orphan_ledger_lines']['count'] === 0
            && $checks['orphan_stock_lines']['count'] === 0;
    }
}