<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpExchangeRate extends Model
{
    use HasFactory;

    protected $table = 'erp_exchange_rates';

    protected $fillable = [
        'company_id',
        'currency_id',
        'effective_date_ad',
        'effective_date_bs',
        'rate_to_base',
    ];

    protected $casts = [
        'effective_date_ad' => 'date',
        'rate_to_base' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(ErpCurrency::class, 'currency_id');
    }
}
