<?php

namespace App\Services\Accounting;

use App\Models\ErpJournalVoucher;

class VoucherNumberService
{
    public function nextNumber(int $companyId, int $branchId, int $fiscalYearId, string $voucherType = 'journal'): string
    {
        $prefix = strtoupper(substr($voucherType, 0, 3));

        $latest = ErpJournalVoucher::query()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('voucher_type', $voucherType)
            ->orderByDesc('id')
            ->first();

        $next = 1;
        if ($latest && preg_match('/(\d+)$/', $latest->voucher_number, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
