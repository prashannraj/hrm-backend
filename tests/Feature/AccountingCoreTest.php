<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpAccountOpeningBalance;
use App\Models\ErpJournalVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_seeded_dashboard_returns_ok(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/accounting/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['company', 'branch', 'fiscalYear', 'totals', 'unpostedVouchers']);
    }

    public function test_journal_voucher_must_balance(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $account = ErpAccount::query()->first();
        $fiscalYearId = \DB::table('erp_fiscal_years')->value('id');

        $response = $this->postJson('/api/v1/accounting/journal-vouchers', [
            'voucher_date_ad' => '2026-07-18',
            'voucher_date_bs' => '2083-04-03',
            'narration' => 'Test journal',
            'lines' => [
                ['account_id' => $account->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $account->id, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_opening_balance_can_be_stored(): void
    {
        $this->seed();

        $user = User::first();
        Sanctum::actingAs($user);

        $account = ErpAccount::query()->first();

        $response = $this->postJson('/api/v1/accounting/opening-balances', [
            'account_id' => $account->id,
            'debit' => 5000,
            'credit' => 0,
            'remarks' => 'Seed opening balance',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('erp_account_opening_balances', [
            'account_id' => $account->id,
            'debit' => 5000,
            'credit' => 0,
        ]);
    }
}
