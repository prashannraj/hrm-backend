<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpBankStatementLine extends Model
{
    use HasFactory;

    protected $table = 'erp_bank_statement_lines';

    protected $fillable = [
        'bank_statement_id', 'txn_date', 'reference', 'description', 'debit', 'credit',
        'amount', 'is_matched', 'matched_voucher_line_id',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
        'amount' => 'float',
        'is_matched' => 'boolean',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(ErpBankStatement::class, 'bank_statement_id');
    }
}
