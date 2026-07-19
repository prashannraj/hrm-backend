<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpCurrency extends Model
{
    use HasFactory;

    protected $table = 'erp_currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ErpExchangeRate::class, 'currency_id');
    }
}
