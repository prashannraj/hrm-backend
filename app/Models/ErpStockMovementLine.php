<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpStockMovementLine extends Model
{
    use HasFactory;

    protected $table = 'erp_stock_movement_lines';

    protected $fillable = [
        'stock_movement_id', 'item_id', 'warehouse_id', 'to_warehouse_id', 'batch_id', 'quantity_in',
        'quantity_out', 'unit_cost', 'total_cost', 'description',
    ];

    protected $casts = [
        'quantity_in' => 'float',
        'quantity_out' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ErpStockMovement::class, 'stock_movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ErpItem::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'to_warehouse_id');
    }

    public function valuationLayers(): HasMany
    {
        return $this->hasMany(ErpStockValuationLayer::class, 'stock_movement_line_id');
    }
}
