<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpVoucherReversal extends Model
{
    use HasFactory;

    protected $table = 'erp_voucher_reversals';

    protected $fillable = [
        'original_voucher_id',
        'reversal_voucher_id',
        'reversed_by',
        'reason',
        'reversed_at',
    ];

    protected $casts = [
        'reversed_at' => 'datetime',
    ];

    public function originalVoucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'original_voucher_id');
    }

    public function reversalVoucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'reversal_voucher_id');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
