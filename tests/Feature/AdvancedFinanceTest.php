<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ErpAccount;
use App\Models\ErpBudget;
use App\Models\ErpFixedAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_asset_can_be_created_and_depreciation_posted(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $assetAccount = ErpAccount::where('code', '1103')->first();
        $expenseAccount = ErpAccount::where('code', '5002')->first();
        $accumulatedAccount = ErpAccount::where('code', '2101')->first();

        $categoryResponse = $this->postJson('/api/v1/advanced-finance/asset-categories', [
            'code' => 'COMP',
            'name' => 'Computer Equipment',
            'asset_account_id' => $assetAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'default_method' => 'straight_line',
            'default_rate' => 20,
        ]);

        $categoryResponse->assertStatus(201);

        $assetResponse = $this->postJson('/api/v1/advanced-finance/fixed-assets', [
            'asset_category_id' => $categoryResponse->json('id'),
            'asset_code' => 'FA-LAP-001',
            'name' => 'Finance Laptop',
            'purchase_date' => '2026-07-01',
            'purchase_cost' => 120000,
            'salvage_value' => 0,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
            'depreciation_rate' => 20,
        ]);

        $assetResponse->assertStatus(201)
            ->assertJsonPath('book_value', 120000);

        $depreciationResponse = $this->postJson('/api/v1/advanced-finance/depreciation-runs', [
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'voucher_date_bs' => '2083-04-15',
            'post_now' => true,
        ]);

        $depreciationResponse->assertStatus(201)
            ->assertJsonPath('status', 'posted');

        $this->assertDatabaseHas('erp_journal_vouchers', [
            'narration' => 'Fixed asset depreciation run #' . $depreciationResponse->json('id'),
            'status' => 'posted',
        ]);

        $asset = ErpFixedAsset::where('asset_code', 'FA-LAP-001')->first();
        $this->assertSame(118000.0, (float) $asset->book_value);
    }

    public function test_hrm_asset_can_be_imported_into_fixed_asset_register(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $hrmAsset = Asset::first();

        $response = $this->postJson('/api/v1/advanced-finance/fixed-assets/import-hrm', [
            'asset_id' => $hrmAsset->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('asset_code', $hrmAsset->code);

        $this->assertDatabaseHas('erp_fixed_assets', [
            'source_asset_id' => $hrmAsset->id,
            'asset_code' => $hrmAsset->code,
        ]);
    }

    public function test_loan_generates_repayment_schedule(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $loanAccount = ErpAccount::where('code', '2101')->first();
        $interestAccount = ErpAccount::where('code', '5002')->first();

        $response = $this->postJson('/api/v1/advanced-finance/loans', [
            'loan_account_id' => $loanAccount->id,
            'interest_account_id' => $interestAccount->id,
            'loan_number' => 'LN-001',
            'lender_name' => 'Nepal Bank',
            'start_date' => '2026-07-18',
            'principal_amount' => 120000,
            'interest_rate' => 12,
            'tenure_months' => 12,
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(12, 'schedules')
            ->assertJsonPath('outstanding_principal', 120000);
    }

    public function test_budget_variance_uses_posted_journal_actuals(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $expenseAccount = ErpAccount::where('code', '5002')->first();
        $cashAccount = ErpAccount::where('code', '1101')->first();

        $budgetResponse = $this->postJson('/api/v1/advanced-finance/budgets', [
            'name' => 'Admin Budget',
            'status' => 'approved',
            'lines' => [
                ['account_id' => $expenseAccount->id, 'amount' => 5000, 'remarks' => 'Office expenses'],
            ],
        ]);

        $budgetResponse->assertStatus(201);

        $this->postJson('/api/v1/accounting/journal-vouchers', [
            'voucher_date_ad' => '2026-07-18',
            'voucher_date_bs' => '2083-04-03',
            'voucher_type' => 'payment',
            'narration' => 'Budget actual expense',
            'post_now' => true,
            'lines' => [
                ['account_id' => $expenseAccount->id, 'debit' => 1200, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => 1200],
            ],
        ])->assertStatus(201);

        $varianceResponse = $this->getJson('/api/v1/advanced-finance/budgets/' . $budgetResponse->json('id') . '/variance');

        $varianceResponse->assertStatus(200)
            ->assertJsonPath('0.budget', 5000)
            ->assertJsonPath('0.actual', 1200)
            ->assertJsonPath('0.variance', 3800);
    }
}
