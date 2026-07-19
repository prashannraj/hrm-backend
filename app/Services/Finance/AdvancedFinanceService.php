<?php

namespace App\Services\Finance;

use App\Models\Asset;
use App\Models\ErpBudget;
use App\Models\ErpCostCenter;
use App\Models\ErpDepreciationRun;
use App\Models\ErpFixedAsset;
use App\Models\ErpFixedAssetCategory;
use App\Models\ErpJournalLine;
use App\Models\ErpLoan;
use App\Models\ErpProject;
use App\Services\Accounting\JournalVoucherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvancedFinanceService
{
    public function __construct(private readonly JournalVoucherService $journalVoucherService)
    {
    }

    public function dashboard(int $companyId): array
    {
        return [
            'fixed_assets' => ErpFixedAsset::where('company_id', $companyId)->count(),
            'asset_book_value' => round((float) ErpFixedAsset::where('company_id', $companyId)->sum('book_value'), 2),
            'active_loans' => ErpLoan::where('company_id', $companyId)->where('status', 'active')->count(),
            'loan_outstanding' => round((float) ErpLoan::where('company_id', $companyId)->sum('outstanding_principal'), 2),
            'cost_centers' => ErpCostCenter::where('company_id', $companyId)->where('is_active', true)->count(),
            'projects' => ErpProject::where('company_id', $companyId)->where('status', 'active')->count(),
            'budgets' => ErpBudget::where('company_id', $companyId)->count(),
        ];
    }

    public function createAssetCategory(array $data): ErpFixedAssetCategory
    {
        return ErpFixedAssetCategory::create(array_merge($data, ['is_active' => $data['is_active'] ?? true]));
    }

    public function createFixedAsset(array $data): ErpFixedAsset
    {
        return ErpFixedAsset::create(array_merge($data, [
            'capitalized_on' => $data['capitalized_on'] ?? $data['purchase_date'],
            'book_value' => $data['book_value'] ?? ((float) $data['purchase_cost'] - (float) ($data['accumulated_depreciation'] ?? 0)),
            'status' => $data['status'] ?? 'active',
        ]));
    }

    public function importHrmAsset(Asset $asset, int $companyId, ?int $categoryId = null): ErpFixedAsset
    {
        return ErpFixedAsset::firstOrCreate(
            ['company_id' => $companyId, 'source_asset_id' => $asset->id],
            [
                'asset_category_id' => $categoryId,
                'asset_code' => $asset->code,
                'name' => $asset->name,
                'purchase_date' => $asset->purchaseDate,
                'capitalized_on' => $asset->purchaseDate,
                'purchase_cost' => $asset->cost,
                'salvage_value' => 0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                'depreciation_rate' => 20,
                'accumulated_depreciation' => 0,
                'book_value' => $asset->cost,
                'status' => strtolower($asset->status) === 'disposed' ? 'disposed' : 'active',
            ]
        );
    }

    public function runDepreciation(array $data, array $context, ?int $userId = null): ErpDepreciationRun
    {
        return DB::transaction(function () use ($data, $context, $userId) {
            $assets = ErpFixedAsset::with('category')
                ->where('company_id', $context['company_id'])
                ->where('status', 'active')
                ->where('book_value', '>', 0)
                ->get();

            $run = ErpDepreciationRun::create([
                'company_id' => $context['company_id'],
                'fiscal_year_id' => $context['fiscal_year_id'],
                'period_from' => $data['period_from'],
                'period_to' => $data['period_to'],
                'status' => 'draft',
                'total_depreciation' => 0,
                'created_by' => $userId,
            ]);

            $total = 0.0;
            foreach ($assets as $asset) {
                $amount = $this->depreciationAmount($asset, $data['period_from'], $data['period_to']);
                if ($amount <= 0) {
                    continue;
                }

                $closing = max((float) $asset->salvage_value, (float) $asset->book_value - $amount);
                $run->lines()->create([
                    'fixed_asset_id' => $asset->id,
                    'opening_book_value' => $asset->book_value,
                    'depreciation_amount' => $amount,
                    'closing_book_value' => $closing,
                ]);

                $asset->update([
                    'accumulated_depreciation' => (float) $asset->accumulated_depreciation + $amount,
                    'book_value' => $closing,
                ]);
                $total += $amount;
            }

            $run->update(['total_depreciation' => $total, 'status' => $data['post_now'] ?? false ? 'posted' : 'draft']);

            if (($data['post_now'] ?? false) && $total > 0) {
                $voucher = $this->depreciationVoucher($run->fresh('lines.asset.category'), $context, $userId, $data);
                $run->update(['journal_voucher_id' => $voucher->id]);
            }

            return $run->fresh(['lines.asset', 'journalVoucher']);
        });
    }

    public function createLoan(array $data): ErpLoan
    {
        return DB::transaction(function () use ($data) {
            $loan = ErpLoan::create(array_merge($data, [
                'outstanding_principal' => $data['outstanding_principal'] ?? $data['principal_amount'],
                'status' => $data['status'] ?? 'active',
            ]));

            $principal = round((float) $data['principal_amount'] / (int) $data['tenure_months'], 2);
            $monthlyInterest = round(((float) $data['principal_amount'] * ((float) $data['interest_rate'] / 100)) / 12, 2);
            $start = Carbon::parse($data['start_date']);

            for ($i = 1; $i <= (int) $data['tenure_months']; $i++) {
                $loan->schedules()->create([
                    'due_date' => $start->copy()->addMonths($i),
                    'principal_due' => $principal,
                    'interest_due' => $monthlyInterest,
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
            }

            return $loan->load('schedules');
        });
    }

    public function createCostCenter(array $data): ErpCostCenter
    {
        return ErpCostCenter::create(array_merge($data, ['is_active' => $data['is_active'] ?? true]));
    }

    public function createProject(array $data): ErpProject
    {
        return ErpProject::create(array_merge($data, ['status' => $data['status'] ?? 'active']));
    }

    public function createBudget(array $data): ErpBudget
    {
        return DB::transaction(function () use ($data) {
            $budget = ErpBudget::create([
                'company_id' => $data['company_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'name' => $data['name'],
                'status' => $data['status'] ?? 'draft',
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $budget->lines()->create($line);
            }

            return $budget->load(['lines.account', 'lines.costCenter', 'lines.project']);
        });
    }

    public function budgetVariance(int $companyId, int $budgetId): array
    {
        $budget = ErpBudget::with(['lines.account'])->where('company_id', $companyId)->findOrFail($budgetId);

        return $budget->lines->map(function ($line) use ($companyId) {
            $actual = ErpJournalLine::where('account_id', $line->account_id)
                ->whereHas('voucher', fn ($query) => $query->where('company_id', $companyId)->where('status', 'posted'))
                ->sum(DB::raw('debit - credit'));

            return [
                'account_id' => $line->account_id,
                'account' => $line->account,
                'budget' => round((float) $line->amount, 2),
                'actual' => round((float) $actual, 2),
                'variance' => round((float) $line->amount - (float) $actual, 2),
            ];
        })->values()->all();
    }

    private function depreciationAmount(ErpFixedAsset $asset, string $from, string $to): float
    {
        $fromMonth = Carbon::parse($from)->startOfMonth();
        $toMonth = Carbon::parse($to)->startOfMonth();
        $months = max(1, ((int) floor($fromMonth->diffInMonths($toMonth))) + 1);
        $base = max(0, (float) $asset->book_value - (float) $asset->salvage_value);

        if ($asset->depreciation_method === 'written_down_value') {
            return round(min($base, (float) $asset->book_value * ((float) $asset->depreciation_rate / 100) * ($months / 12)), 2);
        }

        return round(min($base, (((float) $asset->purchase_cost - (float) $asset->salvage_value) / max(1, (int) $asset->useful_life_months)) * $months), 2);
    }

    private function depreciationVoucher(ErpDepreciationRun $run, array $context, ?int $userId, array $data)
    {
        $category = $run->lines->first()?->asset?->category;
        if (!$category?->depreciation_expense_account_id || !$category?->accumulated_depreciation_account_id) {
            throw ValidationException::withMessages(['asset_category_id' => 'Depreciation accounts are required to post depreciation.']);
        }

        return $this->journalVoucherService->create([
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'fiscal_year_id' => $context['fiscal_year_id'],
            'voucher_type' => 'journal',
            'voucher_date_ad' => $data['period_to'],
            'voucher_date_bs' => $data['voucher_date_bs'] ?? null,
            'narration' => 'Fixed asset depreciation run #' . $run->id,
            'post_now' => true,
            'lines' => [
                ['account_id' => $category->depreciation_expense_account_id, 'debit' => $run->total_depreciation, 'credit' => 0],
                ['account_id' => $category->accumulated_depreciation_account_id, 'debit' => 0, 'credit' => $run->total_depreciation],
            ],
        ], $userId);
    }
}
