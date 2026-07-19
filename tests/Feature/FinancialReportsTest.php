<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_reports_endpoints_return_data(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $account = ErpAccount::query()->first();

        $dashboardResponse = $this->getJson('/api/v1/financial-reports/dashboard?from=2026-07-01&to=2026-07-31');
        $dashboardResponse->assertOk()->assertJsonStructure(['company', 'branch', 'fiscal_year', 'filters', 'totals']);

        $trialBalanceResponse = $this->getJson('/api/v1/financial-reports/trial-balance?from=2026-07-01&to=2026-07-31');
        $trialBalanceResponse->assertOk();

        $ledgerResponse = $this->getJson('/api/v1/financial-reports/general-ledger?from=2026-07-01&to=2026-07-31&account_id=' . $account->id);
        $ledgerResponse->assertOk()->assertJsonStructure(['opening', 'lines', 'closing_balance']);

        $profitLossResponse = $this->getJson('/api/v1/financial-reports/profit-and-loss?from=2026-07-01&to=2026-07-31');
        $profitLossResponse->assertOk()->assertJsonStructure(['income', 'expenses', 'total_income', 'total_expenses', 'net_profit']);

        $balanceSheetResponse = $this->getJson('/api/v1/financial-reports/balance-sheet?to=2026-07-31');
        $balanceSheetResponse->assertOk()->assertJsonStructure(['assets', 'liabilities', 'equity', 'current_period_profit', 'total_assets', 'total_liabilities', 'total_equity', 'total_liabilities_and_equity', 'difference']);

        $cashFlowResponse = $this->getJson('/api/v1/financial-reports/cash-flow?from=2026-07-01&to=2026-07-31');
        $cashFlowResponse->assertOk()->assertJsonStructure(['opening_cash_balance', 'cash_inflows', 'cash_outflows', 'net_cash_change', 'closing_cash_balance', 'cash_accounts']);
    }
}
