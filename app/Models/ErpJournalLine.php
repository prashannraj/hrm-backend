<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpJournalLine extends Model
{
    use HasFactory;

    protected $table = 'erp_journal_lines';

    protected $fillable = [
        'journal_voucher_id',
        'account_id',
        'branch_id',
        'description',
        'debit',
        'credit',
        'line_order',
    ];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
        'line_order' => 'integer',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ErpAccount::class, 'account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'branch_id');
    }
}
