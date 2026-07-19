<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpItemBatch extends Model
{
    use HasFactory;

    protected $table = 'erp_item_batches';

    protected $fillable = ['item_id', 'batch_number', 'manufactured_on', 'expires_on'];

    protected $casts = ['manufactured_on' => 'date', 'expires_on' => 'date'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ErpItem::class, 'item_id');
    }
}
