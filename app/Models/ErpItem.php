<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpItem extends Model
{
    use HasFactory;

    protected $table = 'erp_items';

    protected $fillable = [
        'company_id', 'item_category_id', 'unit_id', 'default_warehouse_id', 'sku', 'name', 'description',
        'barcode', 'qr_code', 'costing_method', 'track_batch', 'track_serial', 'minimum_stock', 'maximum_stock',
        'reorder_level', 'standard_rate', 'vat_rate', 'is_active',
    ];

    protected $casts = [
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'is_active' => 'boolean',
        'minimum_stock' => 'float',
        'maximum_stock' => 'float',
        'reorder_level' => 'float',
        'standard_rate' => 'float',
        'vat_rate' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ErpItemCategory::class, 'item_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ErpUnit::class, 'unit_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'default_warehouse_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ErpItemBatch::class, 'item_id');
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(ErpItemSerialNumber::class, 'item_id');
    }
}
