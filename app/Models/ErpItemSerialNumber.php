<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpItemSerialNumber extends Model
{
    use HasFactory;

    protected $table = 'erp_item_serial_numbers';

    protected $fillable = ['item_id', 'warehouse_id', 'serial_number', 'status'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ErpItem::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'warehouse_id');
    }
}
