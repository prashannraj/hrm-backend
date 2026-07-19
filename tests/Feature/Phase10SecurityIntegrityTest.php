<?php

namespace Tests\Feature;

use App\Models\ErpCompany;
use App\Models\ErpFiscalYear;
use App\Models\ErpAccount;
use App\Models\ErpJournalVoucher;
use App\Models\ErpJournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase10SecurityIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrity_checks_endpoint_returns_data(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/integrity-checks');
        $response->assertOk()
            ->assertJsonStructure([
                'company',
                'fiscal_year',
                'checks' => [
                    'unbalanced_journals',
                    'invoice_gaps',
                    'duplicate_numbers',
                    'negative_stock',
                    'missing_tax_ledgers',
                    'orphan_ledger_lines',
                    'orphan_stock_lines',
                    'is_healthy',
                ],
            ]);
    }

    public function test_user_permissions_endpoint_returns_data(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/user-permissions');
        $response->assertOk()
            ->assertJsonStructure([
                'user',
                'company',
                'permissions',
            ]);
    }

    public function test_fiscal_year_close_endpoint(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $company = ErpCompany::first();
        $fiscalYear = ErpFiscalYear::where('company_id', $company->id)->first();

        $response = $this->postJson('/api/v1/admin/fiscal-year/close');
        $response->assertOk()
            ->assertJsonStructure(['message', 'fiscal_year']);
    }

    public function test_fiscal_year_reopen_endpoint(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/admin/fiscal-year/reopen');
        $response->assertOk()
            ->assertJsonStructure(['message', 'fiscal_year']);
    }

    public function test_unbalanced_journal_detection(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $company = ErpCompany::first();
        $fiscalYear = ErpFiscalYear::where('company_id', $company->id)->first();
        $account = ErpAccount::where('company_id', $company->id)->first();

        // Create an unbalanced voucher
        $voucher = ErpJournalVoucher::create([
            'company_id' => $company->id,
            'fiscal_year_id' => $fiscalYear->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JV-001',
            'voucher_date_ad' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 1000,
            'total_credit' => 500,
        ]);

        ErpJournalLine::create([
            'journal_voucher_id' => $voucher->id,
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
            'line_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/admin/integrity-checks');
        $response->assertOk();
        
        $data = $response->json();
        $this->assertGreaterThan(0, $data['checks']['unbalanced_journals']['count']);
    }

    public function test_all_additional_reports_endpoints(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $reportEndpoints = [
            '/api/v1/financial-reports/day-book',
            '/api/v1/financial-reports/sales-register',
            '/api/v1/financial-reports/purchase-register',
            '/api/v1/financial-reports/receivables',
            '/api/v1/financial-reports/payables',
            '/api/v1/financial-reports/ageing',
            '/api/v1/financial-reports/stock-ledger',
            '/api/v1/financial-reports/inventory-valuation',
            '/api/v1/financial-reports/vat-report',
            '/api/v1/financial-reports/tds-report',
            '/api/v1/financial-reports/audit-report',
        ];

        foreach ($reportEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertOk();
        }
    }

    public function test_export_endpoint(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/financial-reports/export/trial-balance?format=json');
        $response->assertOk()
            ->assertJsonStructure([
                'report',
                'format',
                'company',
                'fiscal_year',
                'generated_at',
                'filters',
                'data',
            ]);
    }
}