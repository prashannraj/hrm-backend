<?php

namespace Tests\Unit;

use App\Models\ErpFiscalYear;
use App\Services\Accounting\FiscalPeriodService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FiscalPeriodServiceTest extends TestCase
{
    public function test_closed_fiscal_year_is_rejected(): void
    {
        $service = new FiscalPeriodService();

        $fiscalYear = new ErpFiscalYear([
            'starts_on_ad' => '2026-04-14',
            'ends_on_ad' => '2027-04-13',
            'is_closed' => true,
        ]);
        $fiscalYear->starts_on_ad = '2026-04-14';
        $fiscalYear->ends_on_ad = '2027-04-13';

        $this->expectException(ValidationException::class);

        $service->assertDateIsOpen($fiscalYear, '2026-07-18');
    }
}
