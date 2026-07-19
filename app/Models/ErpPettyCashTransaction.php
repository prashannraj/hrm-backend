<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpPettyCashTransaction extends Model
{
    use HasFactory;

    protected $table = 'erp_petty_cash_transactions';

    protected $fillable = [
        'petty_cash_fund_id', 'txn_date', 'txn_type', 'reference_number', 'description',
        'amount', 'journal_voucher_id', 'created_by',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'amount' => 'float',
    ];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(ErpPettyCashFund::class, 'petty_cash_fund_id');
    }
}
