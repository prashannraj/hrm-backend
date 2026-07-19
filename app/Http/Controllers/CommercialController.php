<?php

namespace App\Http\Controllers;

use App\Models\ErpCommercialDocument;
use App\Models\ErpParty;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Commercial\CommercialDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommercialController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly CommercialDocumentService $commercialDocumentService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json([
            'customers' => ErpParty::where('company_id', $context['company']->id)->where('party_type', 'customer')->count(),
            'vendors' => ErpParty::where('company_id', $context['company']->id)->where('party_type', 'vendor')->count(),
            'sales' => ErpCommercialDocument::where('company_id', $context['company']->id)->where('document_type', 'sales_invoice')->sum('grand_total'),
            'purchases' => ErpCommercialDocument::where('company_id', $context['company']->id)->where('document_type', 'purchase_bill')->sum('grand_total'),
        ]);
    }

    public function parties(Request $request)
    {
        $context = $this->context();

        return response()->json(ErpParty::with('account')
            ->where('company_id', $context['company']->id)
            ->when($request->input('party_type'), fn ($query, $type) => $query->where('party_type', $type))
            ->orderBy('code')
            ->get());
    }

    public function storeParty(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'account_id' => ['nullable', 'exists:erp_accounts,id'],
            'party_type' => ['required', Rule::in(['customer', 'vendor', 'both'])],
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_parties')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'pan' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(ErpParty::create(array_merge($data, ['company_id' => $context['company']->id]))->load('account'), 201);
    }

    public function documents(Request $request)
    {
        $context = $this->context();

        return response()->json(ErpCommercialDocument::with(['party', 'lines.item', 'journalVoucher'])
            ->where('company_id', $context['company']->id)
            ->when($request->input('document_type'), fn ($query, $type) => $query->where('document_type', $type))
            ->latest('document_date_ad')
            ->get());
    }

    public function storeDocument(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:erp_parties,id'],
            'document_type' => ['required', Rule::in(['quotation', 'sales_order', 'purchase_order', 'delivery_challan', 'purchase_bill', 'sales_invoice', 'purchase_return', 'sales_return'])],
            'document_date_ad' => ['required', 'date'],
            'document_date_bs' => ['nullable', 'string', 'max:20'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'post_now' => ['boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'exists:erp_items,id'],
            'lines.*.account_id' => ['required', 'exists:erp_accounts,id'],
            'lines.*.warehouse_id' => ['nullable', 'exists:erp_warehouses,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.rate' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tds_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $document = $this->commercialDocumentService->create(array_merge($data, [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ]), $request->user()?->id);

        return response()->json($document, 201);
    }

    public function postDocument(Request $request, ErpCommercialDocument $document)
    {
        return response()->json($this->commercialDocumentService->post($document, [], $request->user()?->id));
    }

    public function outstanding(Request $request)
    {
        $context = $this->context();
        $partyType = $request->input('party_type', 'customer');

        return response()->json($this->commercialDocumentService->outstanding($context['company']->id, $partyType));
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
