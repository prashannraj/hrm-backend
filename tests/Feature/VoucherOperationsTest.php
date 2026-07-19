<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpJournalVoucher;
use App\Models\ErpVoucherApproval;
use App\Models\ErpVoucherAttachment;
use App\Models\ErpVoucherReversal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoucherOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_can_be_submitted_and_approved(): void
    {
        $this->seed();
        $user = User::first();
        Sanctum::actingAs($user);

        $account = ErpAccount::query()->first();

        $voucher = ErpJournalVoucher::create([
            'company_id' => 1,
            'branch_id' => 1,
            'fiscal_year_id' => 1,
            'voucher_type' => 'journal',
            'voucher_number' => 'JNL-00001',
            'voucher_date_ad' => now()->toDateString(),
            'status' => 'draft',
            'total_debit' => 100,
            'total_credit' => 100,
            'created_by' => $user->id,
        ]);

        $voucher->lines()->createMany([
            ['account_id' => $account->id, 'debit' => 100, 'credit' => 0, 'line_order' => 1],
            ['account_id' => $account->id, 'debit' => 0, 'credit' => 100, 'line_order' => 2],
        ]);

        $submit = $this->postJson("/api/v1/vouchers/{$voucher->id}/submit", ['remarks' => 'submit']);
        $submit->assertStatus(201);
        $this->assertDatabaseHas('erp_voucher_approvals', ['journal_voucher_id' => $voucher->id, 'status' => 'submitted']);

        $approve = $this->postJson("/api/v1/vouchers/{$voucher->id}/approve", ['remarks' => 'approve']);
        $approve->assertOk();
        $this->assertDatabaseHas('erp_voucher_approvals', ['journal_voucher_id' => $voucher->id, 'status' => 'approved']);
    }

    public function test_voucher_attachments_can_be_uploaded(): void
    {
        $this->seed();
        Storage::fake('public');

        $user = User::first();
        Sanctum::actingAs($user);
        $voucher = ErpJournalVoucher::create([
            'company_id' => 1,
            'branch_id' => 1,
            'fiscal_year_id' => 1,
            'voucher_type' => 'journal',
            'voucher_number' => 'JNL-00002',
            'voucher_date_ad' => now()->toDateString(),
            'status' => 'draft',
            'total_debit' => 100,
            'total_credit' => 100,
            'created_by' => $user->id,
        ]);

        $response = $this->postJson("/api/v1/vouchers/{$voucher->id}/attachments", [
            'file' => UploadedFile::fake()->create('bill.pdf', 100, 'application/pdf'),
            'description' => 'Vendor bill',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('erp_voucher_attachments', ['journal_voucher_id' => $voucher->id, 'file_name' => 'bill.pdf']);
    }

    public function test_posted_voucher_can_be_reversed(): void
    {
        $this->seed();
        $user = User::first();
        Sanctum::actingAs($user);
        $account = ErpAccount::query()->first();

        $voucher = ErpJournalVoucher::create([
            'company_id' => 1,
            'branch_id' => 1,
            'fiscal_year_id' => 1,
            'voucher_type' => 'journal',
            'voucher_number' => 'JNL-00003',
            'voucher_date_ad' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 100,
            'total_credit' => 100,
            'posted_at' => now(),
            'created_by' => $user->id,
            'posted_by' => $user->id,
        ]);

        $voucher->lines()->createMany([
            ['account_id' => $account->id, 'debit' => 100, 'credit' => 0, 'line_order' => 1],
            ['account_id' => $account->id, 'debit' => 0, 'credit' => 100, 'line_order' => 2],
        ]);

        $response = $this->postJson("/api/v1/vouchers/{$voucher->id}/reverse", ['reason' => 'Correction']);
        $response->assertOk();
        $this->assertDatabaseHas('erp_voucher_reversals', ['original_voucher_id' => $voucher->id]);
    }
}
