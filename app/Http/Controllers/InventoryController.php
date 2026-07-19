<?php

namespace App\Http\Controllers;

use App\Models\ErpItem;
use App\Models\ErpItemCategory;
use App\Models\ErpStockMovement;
use App\Models\ErpUnit;
use App\Models\ErpWarehouse;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly StockMovementService $stockMovementService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json([
            'categories' => ErpItemCategory::where('company_id', $context['company']->id)->count(),
            'items' => ErpItem::where('company_id', $context['company']->id)->count(),
            'warehouses' => ErpWarehouse::where('company_id', $context['company']->id)->count(),
            'lowStockItems' => ErpItem::where('company_id', $context['company']->id)->whereColumn('reorder_level', '>', 'minimum_stock')->count(),
        ]);
    }

    public function masters()
    {
        $context = $this->context();

        return response()->json([
            'categories' => ErpItemCategory::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'units' => ErpUnit::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'warehouses' => ErpWarehouse::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'items' => ErpItem::with(['category', 'unit', 'defaultWarehouse'])->where('company_id', $context['company']->id)->orderBy('sku')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:erp_item_categories,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_item_categories')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(ErpItemCategory::create(array_merge($data, ['company_id' => $context['company']->id])), 201);
    }

    public function storeUnit(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('erp_units')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:6'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(ErpUnit::create(array_merge($data, ['company_id' => $context['company']->id])), 201);
    }

    public function storeWarehouse(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_warehouses')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(ErpWarehouse::create(array_merge($data, [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ])), 201);
    }

    public function storeItem(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'item_category_id' => ['required', 'exists:erp_item_categories,id'],
            'unit_id' => ['required', 'exists:erp_units,id'],
            'default_warehouse_id' => ['nullable', 'exists:erp_warehouses,id'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('erp_items')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'costing_method' => ['required', Rule::in(['fifo', 'weighted_average'])],
            'track_batch' => ['boolean'],
            'track_serial' => ['boolean'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'standard_rate' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(ErpItem::create(array_merge($data, ['company_id' => $context['company']->id]))->load(['category', 'unit', 'defaultWarehouse']), 201);
    }

    public function storeMovement(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'movement_type' => ['required', Rule::in(['opening_stock', 'adjustment_in', 'adjustment_out', 'transfer'])],
            'movement_date_ad' => ['required', 'date'],
            'movement_date_bs' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string'],
            'post_now' => ['boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:erp_items,id'],
            'lines.*.warehouse_id' => ['required', 'exists:erp_warehouses,id'],
            'lines.*.to_warehouse_id' => ['nullable', 'exists:erp_warehouses,id'],
            'lines.*.batch_id' => ['nullable', 'exists:erp_item_batches,id'],
            'lines.*.quantity_in' => ['nullable', 'numeric', 'min:0'],
            'lines.*.quantity_out' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ]);

        $movement = $this->stockMovementService->create(array_merge($data, [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ]), $request->user()?->id);

        if ($data['post_now'] ?? false) {
            $movement = $this->stockMovementService->post($movement, $request->user()?->id);
        }

        return response()->json($movement, 201);
    }

    public function postMovement(Request $request, ErpStockMovement $movement)
    {
        return response()->json($this->stockMovementService->post($movement, $request->user()?->id));
    }

    public function ledger(Request $request)
    {
        $context = $this->context();

        return response()->json($this->stockMovementService->stockLedger($context['company']->id, $request->integer('item_id') ?: null));
    }

    public function valuation()
    {
        $context = $this->context();

        return response()->json($this->stockMovementService->valuation($context['company']->id));
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
