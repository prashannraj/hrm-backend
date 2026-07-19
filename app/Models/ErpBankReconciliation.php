<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpBankReconciliation extends Model
{
    use HasFactory;

    protected $table = 'erp_bank_reconciliations';

    protected $fillable = [
        'company_id', 'bank_account_id', 'fiscal_year_id', 'reconciled_on', 'statement_balance',
        'book_balance', 'difference', 'status', 'matched_lines', 'created_by',
    ];

    protected $casts = [
        'reconciled_on' => 'date',
        'statement_balance' => 'float',
        'book_balance' => 'float',
        'difference' => 'float',
        'matched_lines' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(ErpBankAccount::class, 'bank_account_id');
    }
}
