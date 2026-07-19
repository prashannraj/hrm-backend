<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpBankAccount;
use App\Models\ErpPettyCashFund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankingCashReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_account_statement_and_reconciliation_can_be_created(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $bankLedger = ErpAccount::where('code', '1102')->first();
        $incomeLedger = ErpAccount::where('code', '4001')->first();
        $fiscalYearId = \DB::table('erp_fiscal_years')->value('id');

        $bankResponse = $this->postJson('/api/v1/banking/bank-accounts', [
            'account_id' => $bankLedger->id,
            'bank_name' => 'Nabil Bank',
            'account_name' => 'Glow Forward Foundation',
            'account_number' => '0010010001',
            'branch_name' => 'Lalitpur',
            'opening_balance' => 0,
        ]);

        $bankResponse->assertStatus(201);
        $bankAccountId = $bankResponse->json('id');

        $voucherResponse = $this->postJson('/api/v1/accounting/journal-vouchers', [
            'voucher_date_ad' => '2026-07-18',
            'voucher_date_bs' => '2083-04-03',
            'voucher_type' => 'receipt',
            'narration' => 'Bank receipt for reconciliation',
            'post_now' => true,
            'lines' => [
                ['account_id' => $bankLedger->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $incomeLedger->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $voucherResponse->assertStatus(201);

        $statementResponse = $this->postJson('/api/v1/banking/statements', [
            'bank_account_id' => $bankAccountId,
            'statement_date' => '2026-07-18',
            'reference_number' => 'STMT-001',
            'opening_balance' => 0,
            'closing_balance' => 1000,
            'lines' => [
                [
                    'txn_date' => '2026-07-18',
                    'reference' => 'DEP-001',
                    'description' => 'Bank receipt',
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ]);

        $statementResponse->assertStatus(201);

        $reconcileResponse = $this->postJson('/api/v1/banking/reconcile', [
            'bank_account_id' => $bankAccountId,
            'bank_statement_id' => $statementResponse->json('id'),
            'reconciled_on' => '2026-07-18',
            'to_date' => '2026-07-18',
        ]);

        $reconcileResponse->assertStatus(201)
            ->assertJsonPath('difference', 0);

        $this->assertDatabaseHas('erp_bank_statement_lines', [
            'reference' => 'DEP-001',
            'is_matched' => true,
        ]);
    }

    public function test_petty_cash_expense_reduces_fund_and_posts_voucher(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $cashLedger = ErpAccount::where('code', '1101')->first();
        $expenseLedger = ErpAccount::where('code', '5002')->first();

        $fundResponse = $this->postJson('/api/v1/banking/petty-cash-funds', [
            'account_id' => $cashLedger->id,
            'name' => 'Admin Petty Cash',
            'opening_balance' => 5000,
        ]);

        $fundResponse->assertStatus(201);

        $transactionResponse = $this->postJson('/api/v1/banking/petty-cash-transactions', [
            'petty_cash_fund_id' => $fundResponse->json('id'),
            'txn_date' => '2026-07-18',
            'txn_date_bs' => '2083-04-03',
            'txn_type' => 'expense',
            'reference_number' => 'PC-EXP-001',
            'description' => 'Office stationery',
            'amount' => 1200,
            'offset_account_id' => $expenseLedger->id,
        ]);

        $transactionResponse->assertStatus(201);

        $fund = ErpPettyCashFund::find($fundResponse->json('id'));
        $this->assertSame(3800.0, (float) $fund->current_balance);
        $this->assertDatabaseHas('erp_petty_cash_transactions', [
            'reference_number' => 'PC-EXP-001',
            'amount' => 1200,
        ]);
        $this->assertNotNull($transactionResponse->json('journal_voucher_id'));
    }

    public function test_petty_cash_cannot_go_negative(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $cashLedger = ErpAccount::where('code', '1101')->first();
        $fund = ErpPettyCashFund::create([
            'company_id' => \DB::table('erp_companies')->value('id'),
            'account_id' => $cashLedger->id,
            'name' => 'Small Cash Box',
            'opening_balance' => 100,
            'current_balance' => 100,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/banking/petty-cash-transactions', [
            'petty_cash_fund_id' => $fund->id,
            'txn_date' => '2026-07-18',
            'txn_type' => 'expense',
            'amount' => 150,
        ]);

        $response->assertStatus(422);
    }
}
