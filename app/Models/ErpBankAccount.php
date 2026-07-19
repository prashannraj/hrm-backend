<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpBankAccount extends Model
{
    use HasFactory;

    protected $table = 'erp_bank_accounts';

    protected $fillable = [
        'company_id', 'account_id', 'bank_name', 'account_name', 'account_number',
        'branch_name', 'ifsc_code', 'opening_balance', 'is_cash_account', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'is_cash_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ErpAccount::class, 'account_id');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(ErpBankStatement::class, 'bank_account_id');
    }
}
