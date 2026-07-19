<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpDepreciationLine extends Model
{
    use HasFactory;

    protected $table = 'erp_depreciation_lines';

    protected $fillable = ['depreciation_run_id', 'fixed_asset_id', 'opening_book_value', 'depreciation_amount', 'closing_book_value'];
    protected $casts = ['opening_book_value' => 'float', 'depreciation_amount' => 'float', 'closing_book_value' => 'float'];

    public function run(): BelongsTo { return $this->belongsTo(ErpDepreciationRun::class, 'depreciation_run_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(ErpFixedAsset::class, 'fixed_asset_id'); }
}
