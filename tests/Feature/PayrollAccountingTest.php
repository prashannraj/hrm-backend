<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpPayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_settings_and_posted_run_create_balanced_journal(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $salaryExpense = ErpAccount::where('code', '5001')->first();
        $salaryPayable = ErpAccount::where('code', '2104')->first();
        $tdsPayable = ErpAccount::where('code', '2103')->first();
        $statutoryPayable = ErpAccount::where('code', '2101')->first();

        $settingsResponse = $this->postJson('/api/v1/payroll-accounting/settings', [
            'salary_expense_account_id' => $salaryExpense->id,
            'salary_payable_account_id' => $salaryPayable->id,
            'allowance_expense_account_id' => $salaryExpense->id,
            'deduction_account_id' => $statutoryPayable->id,
            'tds_payable_account_id' => $tdsPayable->id,
            'ssf_payable_account_id' => $statutoryPayable->id,
            'cit_payable_account_id' => $statutoryPayable->id,
            'statutory_rates' => ['ssf_employee' => 11, 'ssf_employer' => 20, 'pf' => 0],
        ]);

        $settingsResponse->assertStatus(201)
            ->assertJsonPath('salary_expense_account_id', $salaryExpense->id);

        $runResponse = $this->postJson('/api/v1/payroll-accounting/runs', [
            'period_month' => '2026-07',
            'period_from' => '2026-07-14',
            'period_to' => '2026-07-14',
            'posting_date_ad' => '2026-07-18',
            'posting_date_bs' => '2083-04-03',
            'standard_workdays' => 1,
            'post_now' => true,
        ]);

        $runResponse->assertStatus(201)
            ->assertJsonPath('status', 'posted')
            ->assertJsonCount(5, 'lines');

        $run = ErpPayrollRun::with('journalVoucher.lines')->findOrFail($runResponse->json('id'));

        $this->assertSame('posted', $run->status);
        $this->assertNotNull($run->journal_voucher_id);
        $this->assertSame(
            round($run->journalVoucher->lines->sum('debit'), 2),
            round($run->journalVoucher->lines->sum('credit'), 2)
        );
        $this->assertDatabaseHas('erp_journal_vouchers', [
            'id' => $run->journal_voucher_id,
            'voucher_type' => 'payroll',
            'status' => 'posted',
        ]);
    }

    public function test_posted_payroll_run_can_be_locked_and_reversed(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $salaryExpense = ErpAccount::where('code', '5001')->first();
        $salaryPayable = ErpAccount::where('code', '2104')->first();
        $tdsPayable = ErpAccount::where('code', '2103')->first();
        $statutoryPayable = ErpAccount::where('code', '2101')->first();

        $this->postJson('/api/v1/payroll-accounting/settings', [
            'salary_expense_account_id' => $salaryExpense->id,
            'salary_payable_account_id' => $salaryPayable->id,
            'allowance_expense_account_id' => $salaryExpense->id,
            'deduction_account_id' => $statutoryPayable->id,
            'tds_payable_account_id' => $tdsPayable->id,
            'ssf_payable_account_id' => $statutoryPayable->id,
            'cit_payable_account_id' => $statutoryPayable->id,
        ])->assertStatus(201);

        $runId = $this->postJson('/api/v1/payroll-accounting/runs', [
            'period_month' => '2026-08',
            'period_from' => '2026-07-14',
            'period_to' => '2026-07-14',
            'posting_date_ad' => '2026-07-18',
            'standard_workdays' => 1,
            'post_now' => true,
        ])->assertStatus(201)->json('id');

        $this->postJson('/api/v1/payroll-accounting/runs/' . $runId . '/lock')
            ->assertOk()
            ->assertJsonPath('status', 'locked');

        $this->postJson('/api/v1/payroll-accounting/runs/' . $runId . '/reverse', [
            'reason' => 'Payroll correction',
        ])->assertStatus(201);

        $this->assertDatabaseHas('erp_payroll_runs', [
            'id' => $runId,
            'status' => 'reversed',
        ]);
        $this->assertDatabaseHas('erp_payroll_posting_reversals', [
            'payroll_run_id' => $runId,
            'reason' => 'Payroll correction',
        ]);
    }
}
