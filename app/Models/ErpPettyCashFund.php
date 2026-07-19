<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpPettyCashFund extends Model
{
    use HasFactory;

    protected $table = 'erp_petty_cash_funds';

    protected $fillable = [
        'company_id', 'account_id', 'custodian_id', 'name', 'opening_balance', 'current_balance', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(ErpPettyCashTransaction::class, 'petty_cash_fund_id');
    }
}
