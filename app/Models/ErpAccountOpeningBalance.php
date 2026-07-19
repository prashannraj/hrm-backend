<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpAccountOpeningBalance extends Model
{
    use HasFactory;

    protected $table = 'erp_account_opening_balances';

    protected $fillable = [
        'company_id',
        'branch_id',
        'fiscal_year_id',
        'account_id',
        'debit',
        'credit',
        'remarks',
    ];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'branch_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(ErpFiscalYear::class, 'fiscal_year_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ErpAccount::class, 'account_id');
    }
}
