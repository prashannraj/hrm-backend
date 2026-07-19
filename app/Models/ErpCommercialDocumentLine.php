<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpCommercialDocumentLine extends Model
{
    use HasFactory;

    protected $table = 'erp_commercial_document_lines';

    protected $fillable = [
        'commercial_document_id', 'item_id', 'account_id', 'warehouse_id', 'description',
        'quantity', 'rate', 'discount_rate', 'discount_amount', 'vat_rate', 'vat_amount',
        'tds_rate', 'tds_amount', 'line_total', 'line_order',
    ];

    protected $casts = [
        'quantity' => 'float', 'rate' => 'float', 'discount_rate' => 'float',
        'discount_amount' => 'float', 'vat_rate' => 'float', 'vat_amount' => 'float',
        'tds_rate' => 'float', 'tds_amount' => 'float', 'line_total' => 'float',
        'line_order' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ErpCommercialDocument::class, 'commercial_document_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ErpItem::class, 'item_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ErpAccount::class, 'account_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(ErpWarehouse::class, 'warehouse_id');
    }
}
