<?php

namespace App\Services\Accounting;

use App\Models\ErpJournalVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalVoucherService
{
    public function __construct(
        private readonly FiscalPeriodService $fiscalPeriodService,
        private readonly VoucherNumberService $voucherNumberService,
        private readonly AccountingAuditService $auditService
    ) {
    }

    public function create(array $data, ?int $userId = null): ErpJournalVoucher
    {
        $lines = $data['lines'] ?? [];
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($data, $lines, $userId) {
            $fiscalYear = \App\Models\ErpFiscalYear::findOrFail($data['fiscal_year_id']);
            $this->fiscalPeriodService->assertDateIsOpen($fiscalYear, $data['voucher_date_ad']);

            $voucher = ErpJournalVoucher::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'voucher_type' => $data['voucher_type'] ?? 'journal',
                'voucher_number' => $data['voucher_number'] ?? $this->voucherNumberService->nextNumber(
                    $data['company_id'],
                    $data['branch_id'],
                    $data['fiscal_year_id'],
                    $data['voucher_type'] ?? 'journal'
                ),
                'voucher_date_ad' => $data['voucher_date_ad'],
                'voucher_date_bs' => $data['voucher_date_bs'] ?? null,
                'narration' => $data['narration'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'created_by' => $userId,
                'total_debit' => $this->totalDebit($lines),
                'total_credit' => $this->totalCredit($lines),
            ]);

            foreach (array_values($lines) as $index => $line) {
                $voucher->lines()->create([
                    'account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? $data['branch_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'line_order' => $index + 1,
                ]);
            }

            if (($data['post_now'] ?? false) === true) {
                $this->post($voucher, $userId);
            }

            $this->auditService->record('journal_voucher_created', $voucher->company_id, $voucher, [
                'voucher_number' => $voucher->voucher_number,
                'status' => $voucher->status,
            ]);

            return $voucher->load(['lines.account', 'branch', 'fiscalYear']);
        });
    }

    public function post(ErpJournalVoucher $voucher, ?int $userId = null): ErpJournalVoucher
    {
        if ($voucher->status === 'posted') {
            return $voucher;
        }

        if ($voucher->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => 'Cancelled vouchers cannot be posted.']);
        }

        $voucher->load(['lines', 'fiscalYear']);
        $this->assertBalanced($voucher->lines->toArray());
        $this->fiscalPeriodService->assertDateIsOpen($voucher->fiscalYear, $voucher->voucher_date_ad->format('Y-m-d'));

        $voucher->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $userId,
        ]);

        $this->auditService->record('journal_voucher_posted', $voucher->company_id, $voucher, [
            'voucher_number' => $voucher->voucher_number,
            'total_debit' => $voucher->total_debit,
            'total_credit' => $voucher->total_credit,
        ]);

        return $voucher->fresh(['lines.account', 'branch', 'fiscalYear']);
    }

    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => 'A journal voucher requires at least two lines.']);
        }

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(["lines.$index" => 'A line cannot contain both debit and credit.']);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages(["lines.$index" => 'A line must contain either debit or credit.']);
            }
        }

        if (round($this->totalDebit($lines), 2) !== round($this->totalCredit($lines), 2)) {
            throw ValidationException::withMessages(['lines' => 'Total debit must equal total credit.']);
        }
    }

    private function totalDebit(array $lines): float
    {
        return array_reduce($lines, fn ($total, $line) => $total + (float) ($line['debit'] ?? 0), 0.0);
    }

    private function totalCredit(array $lines): float
    {
        return array_reduce($lines, fn ($total, $line) => $total + (float) ($line['credit'] ?? 0), 0.0);
    }
}
