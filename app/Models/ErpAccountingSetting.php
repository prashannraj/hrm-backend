<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpAccountingSetting extends Model
{
    use HasFactory;

    protected $table = 'erp_accounting_settings';

    protected $fillable = [
        'company_id',
        'default_branch_id',
        'current_fiscal_year_id',
        'base_currency_code',
        'date_display_mode',
        'require_voucher_approval',
        'allow_unpost',
    ];

    protected $casts = [
        'require_voucher_approval' => 'boolean',
        'allow_unpost' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'default_branch_id');
    }

    public function currentFiscalYear(): BelongsTo
    {
        return $this->belongsTo(ErpFiscalYear::class, 'current_fiscal_year_id');
    }
}
