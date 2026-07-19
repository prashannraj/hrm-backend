<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpWarehouse extends Model
{
    use HasFactory;

    protected $table = 'erp_warehouses';

    protected $fillable = ['company_id', 'branch_id', 'code', 'name', 'location', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'branch_id');
    }

    public function stockLines(): HasMany
    {
        return $this->hasMany(ErpStockMovementLine::class, 'warehouse_id');
    }
}
