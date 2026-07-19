<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpAccount extends Model
{
    use HasFactory;

    protected $table = 'erp_accounts';

    protected $fillable = [
        'company_id',
        'account_group_id',
        'parent_id',
        'code',
        'name',
        'type',
        'normal_balance',
        'is_control_account',
        'is_cash_account',
        'is_bank_account',
        'is_tax_account',
        'pan',
        'vat_number',
        'currency_code',
        'allow_manual_posting',
        'is_active',
    ];

    protected $casts = [
        'is_control_account' => 'boolean',
        'is_cash_account' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_tax_account' => 'boolean',
        'allow_manual_posting' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ErpAccountGroup::class, 'account_group_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(ErpJournalLine::class, 'account_id');
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(ErpAccountOpeningBalance::class, 'account_id');
    }
}
