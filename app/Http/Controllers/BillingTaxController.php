<?php

namespace App\Http\Controllers;

use App\Models\ErpBillingProfile;
use App\Models\ErpCommercialDocument;
use App\Models\ErpTaxInvoiceAudit;
use App\Models\ErpTaxPeriodReport;
use App\Models\ErpTaxRate;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Billing\NepalTaxComplianceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingTaxController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly NepalTaxComplianceService $taxComplianceService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json($this->taxComplianceService->dashboard($context['company']->id));
    }

    public function rates(Request $request)
    {
        $context = $this->context();

        return response()->json(ErpTaxRate::where('company_id', $context['company']->id)
            ->when($request->input('tax_type'), fn ($query, $type) => $query->where('tax_type', $type))
            ->orderBy('tax_type')
            ->orderBy('code')
            ->get());
    }

    public function storeRate(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'tax_type' => ['required', Rule::in(['vat', 'tds'])],
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_tax_rates')->where('company_id', $context['company']->id)->where('tax_type', $request->input('tax_type'))],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'section' => ['nullable', 'string', 'max:80'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->taxComplianceService->createRate(array_merge($data, [
            'company_id' => $context['company']->id,
        ])), 201);
    }

    public function profiles()
    {
        $context = $this->context();

        return response()->json(ErpBillingProfile::where('company_id', $context['company']->id)
            ->where('branch_id', $context['branch']->id)
            ->where('fiscal_year_id', $context['fiscalYear']->id)
            ->orderBy('profile_type')
            ->get());
    }

    public function storeProfile(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'profile_type' => ['required', Rule::in(['tax_invoice', 'abbreviated_tax_invoice', 'vat_invoice', 'proforma_invoice', 'debit_note', 'credit_note'])],
            'display_name' => ['required', 'string', 'max:255'],
            'series_prefix' => ['required', 'string', 'max:20'],
            'next_number' => ['nullable', 'integer', 'min:1'],
            'padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'print_layout' => ['nullable', Rule::in(['a4', 'thermal'])],
            'requires_vat' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->taxComplianceService->createProfile(array_merge($data, [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ])), 201);
    }

    public function issueInvoice(Request $request, ErpCommercialDocument $document)
    {
        return response()->json($this->taxComplianceService->issueTaxInvoice($document, $this->contextIds(), $request->user()?->id));
    }

    public function vatReport(Request $request)
    {
        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ]);

        return response()->json($this->taxComplianceService->vatReport($this->contextIds(), $data['period_from'], $data['period_to']), 201);
    }

    public function tdsReport(Request $request)
    {
        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ]);

        return response()->json($this->taxComplianceService->tdsReport($this->contextIds(), $data['period_from'], $data['period_to']), 201);
    }

    public function reports()
    {
        $context = $this->context();

        return response()->json(ErpTaxPeriodReport::where('company_id', $context['company']->id)
            ->latest()
            ->limit(20)
            ->get());
    }

    public function audits(Request $request)
    {
        $context = $this->context();

        if ($request->boolean('refresh')) {
            $this->taxComplianceService->auditDocuments($context['company']->id, $request->user()?->id);
        }

        return response()->json(ErpTaxInvoiceAudit::with('document.party')
            ->where('company_id', $context['company']->id)
            ->latest()
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
