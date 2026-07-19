<?php

namespace App\Http\Controllers;

use App\Models\ErpAccount;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Accounting\FinancialReportService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly FinancialReportService $reportService
    ) {
    }

    public function dashboard(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json([
            'company' => $context['company'],
            'branch' => $context['branch'],
            'fiscal_year' => $context['fiscalYear'],
            'filters' => $filters,
            'totals' => $this->reportService->dashboard($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
        ]);
    }

    public function masters()
    {
        $context = $this->context();

        return response()->json([
            'accounts' => ErpAccount::where('company_id', $context['company']->id)->orderBy('code')->get(),
        ]);
    }

    public function trialBalance(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->trialBalance($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function generalLedger(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);
        $accountId = $request->integer('account_id') ?: null;

        return response()->json($this->reportService->generalLedger($context['company']->id, $context['fiscalYear']->id, $accountId, $filters['from'], $filters['to']));
    }

    public function profitAndLoss(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->profitAndLoss($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function balanceSheet(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->balanceSheet($context['company']->id, $context['fiscalYear']->id, $filters['to']));
    }

    public function cashFlow(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->cashFlow($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    // Phase 9: Additional Report Endpoints

    public function dayBook(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->dayBook($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function salesRegister(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->salesRegister($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function purchaseRegister(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->purchaseRegister($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function receivables(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->receivables($context['company']->id, $context['fiscalYear']->id, $filters['to']));
    }

    public function payables(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->payables($context['company']->id, $context['fiscalYear']->id, $filters['to']));
    }

    public function ageing(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);
        $days = $request->integer('days', 30);

        return response()->json($this->reportService->ageing($context['company']->id, $context['fiscalYear']->id, $filters['to'], $days));
    }

    public function stockLedger(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);
        $itemId = $request->integer('item_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return response()->json($this->reportService->stockLedger($context['company']->id, $context['fiscalYear']->id, $itemId, $warehouseId, $filters['from'], $filters['to']));
    }

    public function inventoryValuation(Request $request)
    {
        $context = $this->context();
        $itemId = $request->integer('item_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return response()->json($this->reportService->inventoryValuation($context['company']->id, $context['fiscalYear']->id, $itemId, $warehouseId));
    }

    public function vatReport(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->vatReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function tdsReport(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->tdsReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    public function auditReport(Request $request)
    {
        $context = $this->context();
        $filters = $this->filters($request);

        return response()->json($this->reportService->auditReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']));
    }

    // Phase 9: Export Endpoints

    public function export(Request $request, string $report)
    {
        $context = $this->context();
        $filters = $this->filters($request);
        $format = $request->get('format', 'json');

        $data = match ($report) {
            'trial-balance' => $this->reportService->trialBalance($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'general-ledger' => $this->reportService->generalLedger($context['company']->id, $context['fiscalYear']->id, $request->integer('account_id') ?: null, $filters['from'], $filters['to']),
            'profit-and-loss' => $this->reportService->profitAndLoss($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'balance-sheet' => $this->reportService->balanceSheet($context['company']->id, $context['fiscalYear']->id, $filters['to']),
            'cash-flow' => $this->reportService->cashFlow($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'day-book' => $this->reportService->dayBook($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'sales-register' => $this->reportService->salesRegister($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'purchase-register' => $this->reportService->purchaseRegister($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'receivables' => $this->reportService->receivables($context['company']->id, $context['fiscalYear']->id, $filters['to']),
            'payables' => $this->reportService->payables($context['company']->id, $context['fiscalYear']->id, $filters['to']),
            'ageing' => $this->reportService->ageing($context['company']->id, $context['fiscalYear']->id, $filters['to'], $request->integer('days', 30)),
            'stock-ledger' => $this->reportService->stockLedger($context['company']->id, $context['fiscalYear']->id, $request->integer('item_id') ?: null, $request->integer('warehouse_id') ?: null, $filters['from'], $filters['to']),
            'inventory-valuation' => $this->reportService->inventoryValuation($context['company']->id, $context['fiscalYear']->id, $request->integer('item_id') ?: null, $request->integer('warehouse_id') ?: null),
            'vat-report' => $this->reportService->vatReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'tds-report' => $this->reportService->tdsReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            'audit-report' => $this->reportService->auditReport($context['company']->id, $context['fiscalYear']->id, $filters['from'], $filters['to']),
            default => null,
        };

        if ($data === null) {
            abort(404, 'Report not found');
        }

        // Return export-ready structure
        return response()->json([
            'report' => $report,
            'format' => $format,
            'company' => $context['company']->name,
            'fiscal_year' => $context['fiscalYear']->name,
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'data' => $data,
        ]);
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ];
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
