<?php

namespace App\Services\Accounting;

use App\Models\ErpJournalVoucher;
use App\Models\ErpVoucherApproval;
use App\Models\ErpVoucherReversal;
use App\Models\ErpVoucherSeries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VoucherOperationService
{
    public function __construct(
        private readonly JournalVoucherService $journalVoucherService,
        private readonly AccountingAuditService $auditService,
        private readonly VoucherNumberService $voucherNumberService
    ) {
    }

    public function seriesFor(array $context): ErpVoucherSeries
    {
        return ErpVoucherSeries::query()->firstOrCreate(
            [
                'company_id' => $context['company_id'],
                'branch_id' => $context['branch_id'],
                'fiscal_year_id' => $context['fiscal_year_id'],
                'voucher_type' => $context['voucher_type'],
            ],
            [
                'prefix' => strtoupper(substr($context['voucher_type'], 0, 3)),
                'next_number' => 1,
                'padding' => 5,
                'is_active' => true,
            ]
        );
    }

    public function nextVoucherNumber(array $context): string
    {
        $series = $this->seriesFor($context);
        $number = $series->prefix . '-' . str_pad((string) $series->next_number, $series->padding, '0', STR_PAD_LEFT);

        $series->increment('next_number');

        return $number;
    }

    public function submitApproval(ErpJournalVoucher $voucher, ?int $userId = null, ?string $remarks = null): ErpVoucherApproval
    {
        $approval = ErpVoucherApproval::updateOrCreate(
            ['journal_voucher_id' => $voucher->id],
            [
                'requested_by' => $userId,
                'status' => 'submitted',
                'remarks' => $remarks,
                'requested_at' => now(),
            ]
        );

        $voucher->update(['status' => 'draft']);

        $this->auditService->record('voucher_submitted_for_approval', $voucher->company_id, $voucher, [
            'voucher_number' => $voucher->voucher_number,
            'remarks' => $remarks,
        ]);

        return $approval;
    }

    public function approve(ErpJournalVoucher $voucher, ?int $userId = null, ?string $remarks = null): ErpVoucherApproval
    {
        $approval = ErpVoucherApproval::query()->firstOrNew(['journal_voucher_id' => $voucher->id]);
        $approval->fill([
            'requested_by' => $approval->requested_by ?? $userId,
            'approved_by' => $userId,
            'status' => 'approved',
            'remarks' => $remarks,
            'requested_at' => $approval->requested_at ?? now(),
            'approved_at' => now(),
        ]);
        $approval->save();

        $voucher->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $this->auditService->record('voucher_approved', $voucher->company_id, $voucher, [
            'voucher_number' => $voucher->voucher_number,
            'remarks' => $remarks,
        ]);

        return $approval;
    }

    public function cancel(ErpJournalVoucher $voucher, ?int $userId = null, string $reason = ''): ErpJournalVoucher
    {
        if (in_array($voucher->status, ['posted', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or approved vouchers can be cancelled.']);
        }

        $voucher->update([
            'status' => 'cancelled',
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->auditService->record('voucher_cancelled', $voucher->company_id, $voucher, [
            'voucher_number' => $voucher->voucher_number,
            'reason' => $reason,
        ]);

        return $voucher->fresh();
    }

    public function attachFile(ErpJournalVoucher $voucher, UploadedFile $file, ?int $userId = null, ?string $description = null)
    {
        $path = $file->store("erp/vouchers/{$voucher->id}", 'public');

        $attachment = $voucher->attachments()->create([
            'uploaded_by' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'description' => $description,
        ]);

        $this->auditService->record('voucher_attachment_added', $voucher->company_id, $voucher, [
            'file_name' => $attachment->file_name,
            'file_path' => $path,
        ]);

        return $attachment;
    }

    public function reverse(ErpJournalVoucher $voucher, ?int $userId = null, string $reason = ''): ErpJournalVoucher
    {
        if ($voucher->status !== 'posted') {
            throw ValidationException::withMessages(['status' => 'Only posted vouchers can be reversed.']);
        }

        return DB::transaction(function () use ($voucher, $userId, $reason) {
            $reversalNumber = $this->voucherNumberService->nextNumber(
                $voucher->company_id,
                $voucher->branch_id,
                $voucher->fiscal_year_id,
                'journal'
            );

            $reversal = ErpJournalVoucher::create([
                'company_id' => $voucher->company_id,
                'branch_id' => $voucher->branch_id,
                'fiscal_year_id' => $voucher->fiscal_year_id,
                'voucher_type' => 'journal',
                'voucher_number' => $reversalNumber,
                'voucher_date_ad' => now()->toDateString(),
                'voucher_date_bs' => null,
                'narration' => 'Reversal of ' . $voucher->voucher_number,
                'status' => 'posted',
                'total_debit' => $voucher->total_credit,
                'total_credit' => $voucher->total_debit,
                'posted_at' => now(),
                'created_by' => $userId,
                'posted_by' => $userId,
            ]);

            foreach ($voucher->lines as $index => $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'branch_id' => $line->branch_id,
                    'description' => 'Reversal: ' . ($line->description ?? $voucher->voucher_number),
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'line_order' => $index + 1,
                ]);
            }

            ErpVoucherReversal::create([
                'original_voucher_id' => $voucher->id,
                'reversal_voucher_id' => $reversal->id,
                'reversed_by' => $userId,
                'reason' => $reason,
                'reversed_at' => now(),
            ]);

            $this->auditService->record('voucher_reversed', $voucher->company_id, $voucher, [
                'original_voucher_number' => $voucher->voucher_number,
                'reversal_voucher_number' => $reversal->voucher_number,
                'reason' => $reason,
            ]);

            return $reversal->load('lines.account');
        });
    }
}
