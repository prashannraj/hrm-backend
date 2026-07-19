<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpTaxRate extends Model
{
    use HasFactory;

    protected $table = 'erp_tax_rates';

    protected $fillable = [
        'company_id', 'tax_type', 'code', 'name', 'rate', 'section',
        'effective_from', 'effective_to', 'is_active',
    ];

    protected $casts = [
        'rate' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }
}
