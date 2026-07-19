<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpFiscalYear extends Model
{
    use HasFactory;

    protected $table = 'erp_fiscal_years';

    protected $fillable = [
        'company_id',
        'name',
        'starts_on_ad',
        'ends_on_ad',
        'starts_on_bs',
        'ends_on_bs',
        'is_current',
        'is_closed',
    ];

    protected $casts = [
        'starts_on_ad' => 'date',
        'ends_on_ad' => 'date',
        'is_current' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(ErpAccountOpeningBalance::class, 'fiscal_year_id');
    }

    public function journalVouchers(): HasMany
    {
        return $this->hasMany(ErpJournalVoucher::class, 'fiscal_year_id');
    }
}
