<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpBankStatement extends Model
{
    use HasFactory;

    protected $table = 'erp_bank_statements';

    protected $fillable = [
        'company_id', 'bank_account_id', 'fiscal_year_id', 'statement_date', 'reference_number',
        'opening_balance', 'closing_balance', 'status', 'remarks', 'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'opening_balance' => 'float',
        'closing_balance' => 'float',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(ErpBankAccount::class, 'bank_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ErpBankStatementLine::class, 'bank_statement_id');
    }
}
