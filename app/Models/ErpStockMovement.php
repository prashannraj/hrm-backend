<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpStockMovement extends Model
{
    use HasFactory;

    protected $table = 'erp_stock_movements';

    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'movement_type', 'movement_number', 'movement_date_ad',
        'movement_date_bs', 'reference_type', 'reference_id', 'status', 'remarks', 'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = ['movement_date_ad' => 'date', 'posted_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'branch_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(ErpFiscalYear::class, 'fiscal_year_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ErpStockMovementLine::class, 'stock_movement_id');
    }
}
