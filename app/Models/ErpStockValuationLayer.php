<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpStockValuationLayer extends Model
{
    use HasFactory;

    protected $table = 'erp_stock_valuation_layers';

    protected $fillable = [
        'stock_movement_line_id', 'item_id', 'warehouse_id', 'quantity_in', 'quantity_out',
        'remaining_quantity', 'unit_cost', 'value',
    ];

    protected $casts = [
        'quantity_in' => 'float',
        'quantity_out' => 'float',
        'remaining_quantity' => 'float',
        'unit_cost' => 'float',
        'value' => 'float',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(ErpStockMovementLine::class, 'stock_movement_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ErpItem::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'warehouse_id');
    }
}
