<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ErpAccount;
use App\Models\ErpBudget;
use App\Models\ErpCostCenter;
use App\Models\ErpDepreciationRun;
use App\Models\ErpFixedAsset;
use App\Models\ErpFixedAssetCategory;
use App\Models\ErpLoan;
use App\Models\ErpProject;
use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Finance\AdvancedFinanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdvancedFinanceController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly AdvancedFinanceService $financeService
    ) {
    }

    public function dashboard()
    {
        $context = $this->context();

        return response()->json($this->financeService->dashboard($context['company']->id));
    }

    public function masters()
    {
        $context = $this->context();

        return response()->json([
            'accounts' => ErpAccount::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'asset_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'asset')->orderBy('code')->get(),
            'expense_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'expense')->orderBy('code')->get(),
            'liability_accounts' => ErpAccount::where('company_id', $context['company']->id)->where('type', 'liability')->orderBy('code')->get(),
            'categories' => ErpFixedAssetCategory::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'cost_centers' => ErpCostCenter::where('company_id', $context['company']->id)->orderBy('code')->get(),
            'projects' => ErpProject::with('costCenter')->where('company_id', $context['company']->id)->orderBy('code')->get(),
            'hrm_assets' => Asset::orderBy('code')->get(),
        ]);
    }

    public function assetCategories()
    {
        $context = $this->context();

        return response()->json(ErpFixedAssetCategory::where('company_id', $context['company']->id)->orderBy('code')->get());
    }

    public function storeAssetCategory(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_fixed_asset_categories')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'asset_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'depreciation_expense_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'accumulated_depreciation_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'default_method' => ['nullable', Rule::in(['straight_line', 'written_down_value'])],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->financeService->createAssetCategory(array_merge($data, [
            'company_id' => $context['company']->id,
        ])), 201);
    }

    public function fixedAssets()
    {
        $context = $this->context();

        return response()->json(ErpFixedAsset::with(['category', 'depreciationLines', 'project', 'costCenter'])
            ->where('company_id', $context['company']->id)
            ->latest()
            ->limit(100)
            ->get());
    }

    public function storeFixedAsset(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'asset_category_id' => ['nullable', Rule::exists('erp_fixed_asset_categories', 'id')->where('company_id', $context['company']->id)],
            'asset_code' => ['required', 'string', 'max:80', Rule::unique('erp_fixed_assets')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'capitalized_on' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['required', Rule::in(['straight_line', 'written_down_value'])],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0'],
            'project_id' => ['nullable', Rule::exists('erp_projects', 'id')->where('company_id', $context['company']->id)],
            'cost_center_id' => ['nullable', Rule::exists('erp_cost_centers', 'id')->where('company_id', $context['company']->id)],
        ]);

        return response()->json($this->financeService->createFixedAsset(array_merge($data, [
            'company_id' => $context['company']->id,
        ]))->load(['category', 'project', 'costCenter']), 201);
    }

    public function importHrmAsset(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')],
            'asset_category_id' => ['nullable', Rule::exists('erp_fixed_asset_categories', 'id')->where('company_id', $context['company']->id)],
        ]);

        return response()->json($this->financeService->importHrmAsset(
            Asset::findOrFail($data['asset_id']),
            $context['company']->id,
            $data['asset_category_id'] ?? null
        )->load('category'), 201);
    }

    public function runDepreciation(Request $request)
    {
        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'voucher_date_bs' => ['nullable', 'string', 'max:20'],
            'post_now' => ['boolean'],
        ]);

        return response()->json($this->financeService->runDepreciation($data, $this->contextIds(), $request->user()?->id), 201);
    }

    public function depreciationRuns()
    {
        $context = $this->context();

        return response()->json(ErpDepreciationRun::with(['lines.asset', 'journalVoucher'])
            ->where('company_id', $context['company']->id)
            ->latest()
            ->limit(30)
            ->get());
    }

    public function loans()
    {
        $context = $this->context();

        return response()->json(ErpLoan::with(['loanAccount', 'interestAccount', 'schedules'])
            ->where('company_id', $context['company']->id)
            ->latest()
            ->limit(50)
            ->get());
    }

    public function storeLoan(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'loan_account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'interest_account_id' => ['nullable', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'loan_number' => ['required', 'string', 'max:80', Rule::unique('erp_loans')->where('company_id', $context['company']->id)],
            'lender_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'tenure_months' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'closed', 'defaulted'])],
        ]);

        return response()->json($this->financeService->createLoan(array_merge($data, [
            'company_id' => $context['company']->id,
        ])), 201);
    }

    public function costCenters()
    {
        $context = $this->context();

        return response()->json(ErpCostCenter::with('projects')->where('company_id', $context['company']->id)->orderBy('code')->get());
    }

    public function storeCostCenter(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_cost_centers')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'manager' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        return response()->json($this->financeService->createCostCenter(array_merge($data, [
            'company_id' => $context['company']->id,
        ])), 201);
    }

    public function projects()
    {
        $context = $this->context();

        return response()->json(ErpProject::with('costCenter')->where('company_id', $context['company']->id)->orderBy('code')->get());
    }

    public function storeProject(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'cost_center_id' => ['nullable', Rule::exists('erp_cost_centers', 'id')->where('company_id', $context['company']->id)],
            'code' => ['required', 'string', 'max:40', Rule::unique('erp_projects')->where('company_id', $context['company']->id)],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'on_hold', 'cancelled'])],
        ]);

        return response()->json($this->financeService->createProject(array_merge($data, [
            'company_id' => $context['company']->id,
        ]))->load('costCenter'), 201);
    }

    public function budgets()
    {
        $context = $this->context();

        return response()->json(ErpBudget::with(['lines.account', 'lines.costCenter', 'lines.project'])
            ->where('company_id', $context['company']->id)
            ->latest()
            ->limit(50)
            ->get());
    }

    public function storeBudget(Request $request)
    {
        $context = $this->context();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'approved', 'locked'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', Rule::exists('erp_accounts', 'id')->where('company_id', $context['company']->id)],
            'lines.*.cost_center_id' => ['nullable', Rule::exists('erp_cost_centers', 'id')->where('company_id', $context['company']->id)],
            'lines.*.project_id' => ['nullable', Rule::exists('erp_projects', 'id')->where('company_id', $context['company']->id)],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
            'lines.*.remarks' => ['nullable', 'string'],
        ]);

        return response()->json($this->financeService->createBudget(array_merge($data, [
            'company_id' => $context['company']->id,
            'fiscal_year_id' => $context['fiscalYear']->id,
        ])), 201);
    }

    public function budgetVariance(ErpBudget $budget)
    {
        $context = $this->context();
        abort_if((int) $budget->company_id !== (int) $context['company']->id, 404);

        return response()->json($this->financeService->budgetVariance($context['company']->id, $budget->id));
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
