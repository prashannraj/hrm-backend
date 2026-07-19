<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpTaxPeriodReport extends Model
{
    use HasFactory;

    protected $table = 'erp_tax_period_reports';

    protected $fillable = [
        'company_id', 'fiscal_year_id', 'report_type', 'period_from', 'period_to',
        'taxable_sales', 'sales_vat', 'taxable_purchases', 'purchase_vat',
        'tds_deducted', 'net_payable', 'status', 'metadata',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'taxable_sales' => 'float',
        'sales_vat' => 'float',
        'taxable_purchases' => 'float',
        'purchase_vat' => 'float',
        'tds_deducted' => 'float',
        'net_payable' => 'float',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(ErpFiscalYear::class, 'fiscal_year_id');
    }
}
