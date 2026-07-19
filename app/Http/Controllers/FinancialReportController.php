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
