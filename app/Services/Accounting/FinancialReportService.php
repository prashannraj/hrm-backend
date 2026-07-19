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

    // Phase 9: Additional Report Services

    public function dayBook(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): Collection
    {
        return DB::table('erp_journal_vouchers')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'posted')
            ->when($from, fn ($query) => $query->whereDate('voucher_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('voucher_date_ad', '<=', $to))
            ->orderBy('voucher_date_ad')
            ->orderBy('voucher_number')
            ->select(
                'id',
                'voucher_type',
                'voucher_number',
                'voucher_date_ad',
                'voucher_date_bs',
                'narration',
                'total_debit',
                'total_credit'
            )
            ->get()
            ->map(fn ($voucher) => (array) $voucher);
    }

    public function salesRegister(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $documents = DB::table('erp_commercial_documents')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereIn('document_type', ['sales_invoice', 'tax_invoice', 'abbreviated_tax_invoice', 'proforma_invoice'])
            ->where('status', 'posted')
            ->when($from, fn ($query) => $query->whereDate('document_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('document_date_ad', '<=', $to))
            ->orderBy('document_date_ad')
            ->orderBy('document_number')
            ->select(
                'id',
                'document_type',
                'document_number',
                'document_date_ad',
                'document_date_bs',
                'party_id',
                'subtotal',
                'discount_total',
                'vat_total',
                'tds_total',
                'grand_total'
            )
            ->get();

        $totalSales = round((float) $documents->sum('grand_total'), 2);
        $totalVat = round((float) $documents->sum('vat_total'), 2);

        return [
            'documents' => $documents->map(fn ($doc) => (array) $doc),
            'total_sales' => $totalSales,
            'total_vat' => $totalVat,
        ];
    }

    public function purchaseRegister(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $documents = DB::table('erp_commercial_documents')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereIn('document_type', ['purchase_bill', 'purchase_return'])
            ->where('status', 'posted')
            ->when($from, fn ($query) => $query->whereDate('document_date_ad', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('document_date_ad', '<=', $to))
            ->orderBy('document_date_ad')
            ->orderBy('document_number')
            ->select(
                'id',
                'document_type',
                'document_number',
                'document_date_ad',
                'document_date_bs',
                'party_id',
                'subtotal',
                'discount_total',
                'vat_total',
                'tds_total',
                'grand_total'
            )
            ->get();

        $totalPurchases = round((float) $documents->sum('grand_total'), 2);
        $totalVat = round((float) $documents->sum('vat_total'), 2);

        return [
            'documents' => $documents->map(fn ($doc) => (array) $doc),
            'total_purchases' => $totalPurchases,
            'total_vat' => $totalVat,
        ];
    }

    public function receivables(int $companyId, int $fiscalYearId, ?string $asOf = null): array
    {
        $partyAccountIds = DB::table('erp_parties')
            ->where('company_id', $companyId)
            ->whereIn('party_type', ['customer', 'both'])
            ->pluck('account_id');

        $balances = $this->accountBalances($companyId, $fiscalYearId, null, $asOf);

        $receivables = $balances->whereIn('id', $partyAccountIds->toArray())->map(function ($row) {
            $balance = (float) $row->opening_debit + (float) $row->movement_debit - (float) $row->opening_credit - (float) $row->movement_credit;
            return [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'balance' => round($balance, 2),
            ];
        })->filter(fn ($item) => $item['balance'] > 0)->values();

        return [
            'receivables' => $receivables,
            'total_receivables' => round((float) $receivables->sum('balance'), 2),
        ];
    }

    public function payables(int $companyId, int $fiscalYearId, ?string $asOf = null): array
    {
        $partyAccountIds = DB::table('erp_parties')
            ->where('company_id', $companyId)
            ->whereIn('party_type', ['vendor', 'both'])
            ->pluck('account_id');

        $balances = $this->accountBalances($companyId, $fiscalYearId, null, $asOf);

        $payables = $balances->whereIn('id', $partyAccountIds->toArray())->map(function ($row) {
            $balance = (float) $row->opening_credit + (float) $row->movement_credit - (float) $row->opening_debit - (float) $row->movement_debit;
            return [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'balance' => round($balance, 2),
            ];
        })->filter(fn ($item) => $item['balance'] > 0)->values();

        return [
            'payables' => $payables,
            'total_payables' => round((float) $payables->sum('balance'), 2),
        ];
    }

    public function ageing(int $companyId, int $fiscalYearId, ?string $asOf = null, int $days = 30): array
    {
        $asOfDate = $asOf ? \Carbon\Carbon::parse($asOf) : \Carbon\Carbon::now();
        $partyAccountIds = DB::table('erp_parties')
            ->where('company_id', $companyId)
            ->pluck('account_id');

        $balances = $this->accountBalances($companyId, $fiscalYearId, null, $asOf);

        $ageingData = $balances->whereIn('id', $partyAccountIds->toArray())->map(function ($row) use ($asOfDate, $days) {
            $accountId = (int) $row->id;
            $currentBalance = (float) $row->opening_debit + (float) $row->movement_debit - (float) $row->opening_credit - (float) $row->movement_credit;

            if ($currentBalance <= 0) {
                return null;
            }

            // Get last transaction date
            $lastTxn = DB::table('erp_journal_lines')
                ->join('erp_journal_vouchers', 'erp_journal_vouchers.id', '=', 'erp_journal_lines.journal_voucher_id')
                ->where('erp_journal_lines.account_id', $accountId)
                ->where('erp_journal_vouchers.company_id', $companyId)
                ->where('erp_journal_vouchers.fiscal_year_id', $fiscalYearId)
                ->where('erp_journal_vouchers.status', 'posted')
                ->orderByDesc('erp_journal_vouchers.voucher_date_ad')
                ->first();

            $lastTxnDate = $lastTxn ? \Carbon\Carbon::parse($lastTxn->voucher_date_ad) : $asOfDate;
            $daysOverdue = $asOfDate->diffInDays($lastTxnDate);

            $bucket = $daysOverdue <= $days ? 'current' : ($daysOverdue <= $days * 2 ? 'over_30' : ($daysOverdue <= $days * 3 ? 'over_60' : 'over_90'));

            return [
                'account_id' => $accountId,
                'code' => $row->code,
                'name' => $row->name,
                'balance' => round($currentBalance, 2),
                'last_transaction_date' => $lastTxnDate->toDateString(),
                'days_overdue' => $daysOverdue,
                'bucket' => $bucket,
            ];
        })->filter()->values();

        $buckets = [
            'current' => $ageingData->where('bucket', 'current')->values(),
            'over_30' => $ageingData->where('bucket', 'over_30')->values(),
            'over_60' => $ageingData->where('bucket', 'over_60')->values(),
            'over_90' => $ageingData->where('bucket', 'over_90')->values(),
        ];

        return [
            'ageing' => $buckets,
            'total_current' => round((float) $buckets['current']->sum('balance'), 2),
            'total_over_30' => round((float) $buckets['over_30']->sum('balance'), 2),
            'total_over_60' => round((float) $buckets['over_60']->sum('balance'), 2),
            'total_over_90' => round((float) $buckets['over_90']->sum('balance'), 2),
            'total_all' => round((float) $ageingData->sum('balance'), 2),
        ];
    }

    public function stockLedger(int $companyId, int $fiscalYearId, ?int $itemId = null, ?int $warehouseId = null, ?string $from = null, ?string $to = null): array
    {
        $openingQuery = DB::table('erp_stock_valuation_layers')
            ->where('company_id', $companyId)
            ->when($itemId, fn ($q) => $q->where('item_id', $itemId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->sum('value');

        $openingValue = round((float) $openingQuery, 2);

        $lines = DB::table('erp_stock_movement_lines')
            ->join('erp_stock_movements', 'erp_stock_movements.id', '=', 'erp_stock_movement_lines.stock_movement_id')
            ->join('erp_items', 'erp_items.id', '=', 'erp_stock_movement_lines.item_id')
            ->leftJoin('erp_warehouses', 'erp_warehouses.id', '=', 'erp_stock_movement_lines.warehouse_id')
            ->where('erp_stock_movements.company_id', $companyId)
            ->where('erp_stock_movements.fiscal_year_id', $fiscalYearId)
            ->where('erp_stock_movements.status', 'posted')
            ->when($itemId, fn ($q) => $q->where('erp_stock_movement_lines.item_id', $itemId))
            ->when($warehouseId, fn ($q) => $q->where('erp_stock_movement_lines.warehouse_id', $warehouseId))
            ->when($from, fn ($q) => $q->whereDate('erp_stock_movements.movement_date_ad', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('erp_stock_movements.movement_date_ad', '<=', $to))
            ->orderBy('erp_stock_movements.movement_date_ad')
            ->orderBy('erp_stock_movements.movement_number')
            ->select(
                'erp_stock_movement_lines.id',
                'erp_items.sku as item_sku',
                'erp_items.name as item_name',
                'erp_warehouses.code as warehouse_code',
                'erp_warehouses.name as warehouse_name',
                'erp_stock_movement_lines.quantity_in',
                'erp_stock_movement_lines.quantity_out',
                'erp_stock_movement_lines.unit_cost',
                'erp_stock_movement_lines.total_cost',
                'erp_stock_movements.movement_number',
                'erp_stock_movements.movement_date_ad',
                'erp_stock_movements.movement_type'
            )
            ->get();

        $runningQty = 0;
        $runningValue = 0;
        $lines = $lines->map(function ($line) use (&$runningQty, &$runningValue) {
            $runningQty = round($runningQty + (float) $line->quantity_in - (float) $line->quantity_out, 2);
            $runningValue = round($runningValue + (float) $line->total_cost, 2);
            $line->running_quantity = $runningQty;
            $line->running_value = $runningValue;
            return $line;
        });

        return [
            'opening_value' => $openingValue,
            'lines' => $lines,
            'closing_value' => $openingValue + $lines->sum('total_cost'),
        ];
    }

    public function inventoryValuation(int $companyId, int $fiscalYearId, ?int $itemId = null, ?int $warehouseId = null): array
    {
        $query = DB::table('erp_stock_valuation_layers')
            ->where('company_id', $companyId)
            ->when($itemId, fn ($q) => $q->where('item_id', $itemId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->select(
                'item_id',
                'warehouse_id',
                \Illuminate\Support\Facades\DB::raw('SUM(remaining_quantity) as quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(value) as value')
            )
            ->groupBy('item_id', 'warehouse_id')
            ->orderBy('item_id');

        $valuation = $query->get()->map(function ($row) {
            return [
                'item_id' => (int) $row->item_id,
                'warehouse_id' => $row->warehouse_id ? (int) $row->warehouse_id : null,
                'quantity' => round((float) $row->quantity, 2),
                'value' => round((float) $row->value, 2),
            ];
        });

        return [
            'valuation' => $valuation,
            'total_quantity' => round((float) $valuation->sum('quantity'), 2),
            'total_value' => round((float) $valuation->sum('value'), 2),
        ];
    }

    public function vatReport(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $salesVat = DB::table('erp_commercial_document_lines')
            ->join('erp_commercial_documents', 'erp_commercial_documents.id', '=', 'erp_commercial_document_lines.commercial_document_id')
            ->where('erp_commercial_documents.company_id', $companyId)
            ->where('erp_commercial_documents.fiscal_year_id', $fiscalYearId)
            ->whereIn('erp_commercial_documents.document_type', ['sales_invoice', 'tax_invoice', 'abbreviated_tax_invoice'])
            ->where('erp_commercial_documents.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '<=', $to))
            ->sum('vat_amount');

        $purchaseVat = DB::table('erp_commercial_document_lines')
            ->join('erp_commercial_documents', 'erp_commercial_documents.id', '=', 'erp_commercial_document_lines.commercial_document_id')
            ->where('erp_commercial_documents.company_id', $companyId)
            ->where('erp_commercial_documents.fiscal_year_id', $fiscalYearId)
            ->whereIn('erp_commercial_documents.document_type', ['purchase_bill', 'purchase_return'])
            ->where('erp_commercial_documents.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '<=', $to))
            ->sum('vat_amount');

        return [
            'sales_vat' => round((float) $salesVat, 2),
            'purchase_vat' => round((float) $purchaseVat, 2),
            'net_vat_payable' => round((float) $salesVat - (float) $purchaseVat, 2),
        ];
    }

    public function tdsReport(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $tdsDeducted = DB::table('erp_commercial_document_lines')
            ->join('erp_commercial_documents', 'erp_commercial_documents.id', '=', 'erp_commercial_document_lines.commercial_document_id')
            ->where('erp_commercial_documents.company_id', $companyId)
            ->where('erp_commercial_documents.fiscal_year_id', $fiscalYearId)
            ->where('erp_commercial_documents.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('erp_commercial_documents.document_date_ad', '<=', $to))
            ->sum('tds_amount');

        return [
            'tds_deducted' => round((float) $tdsDeducted, 2),
        ];
    }

    public function auditReport(int $companyId, int $fiscalYearId, ?string $from = null, ?string $to = null): array
    {
        $cancelledInvoices = DB::table('erp_commercial_documents')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'cancelled')
            ->count();

        $invoiceGaps = DB::table('erp_billing_profiles')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->map(function ($profile) {
                $usedNumbers = DB::table('erp_commercial_documents')
                    ->where('company_id', $profile->company_id)
                    ->where('fiscal_year_id', $profile->fiscal_year_id)
                    ->where('document_type', $profile->profile_type)
                    ->pluck('document_number');

                $gaps = [];
                $expected = $profile->next_number;
                for ($i = 1; $i < $expected; $i++) {
                    $expectedNumber = $profile->prefix . str_pad((string) $i, $profile->padding, '0', STR_PAD_LEFT);
                    if (!in_array($expectedNumber, $usedNumbers->toArray())) {
                        $gaps[] = $expectedNumber;
                    }
                }
                return $gaps;
            })
            ->flatten()
            ->count();

        $duplicatePans = DB::table('erp_parties')
            ->where('company_id', $companyId)
            ->whereNotNull('pan')
            ->groupBy('pan')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return [
            'cancelled_invoices' => $cancelledInvoices,
            'invoice_gaps' => $invoiceGaps,
            'duplicate_pans' => $duplicatePans,
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
