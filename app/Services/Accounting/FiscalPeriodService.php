<?php

namespace App\Services\Accounting;

use App\Models\ErpFiscalYear;
use App\Models\ErpJournalVoucher;
use Illuminate\Validation\ValidationException;

class FiscalPeriodService
{
    public function assertDateIsOpen(ErpFiscalYear $fiscalYear, string $voucherDateAd): void
    {
        if ($fiscalYear->is_closed) {
            throw ValidationException::withMessages([
                'fiscal_year_id' => 'The selected fiscal year is closed.',
            ]);
        }

        $date = date('Y-m-d', strtotime($voucherDateAd));
        $start = $fiscalYear->starts_on_ad->format('Y-m-d');
        $end = $fiscalYear->ends_on_ad->format('Y-m-d');

        if ($date < $start || $date > $end) {
            throw ValidationException::withMessages([
                'voucher_date_ad' => 'Voucher date must fall within the selected Nepal fiscal year.',
            ]);
        }
    }
}
