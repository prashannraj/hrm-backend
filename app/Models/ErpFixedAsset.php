<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpFixedAsset extends Model
{
    use HasFactory;

    protected $table = 'erp_fixed_assets';

    protected $fillable = [
        'company_id', 'asset_category_id', 'source_asset_id', 'asset_code', 'name', 'purchase_date',
        'capitalized_on', 'purchase_cost', 'salvage_value', 'useful_life_months', 'depreciation_method',
        'depreciation_rate', 'accumulated_depreciation', 'book_value', 'status', 'project_id', 'cost_center_id',
    ];

    protected $casts = [
        'purchase_date' => 'date', 'capitalized_on' => 'date', 'purchase_cost' => 'float',
        'salvage_value' => 'float', 'depreciation_rate' => 'float', 'accumulated_depreciation' => 'float',
        'book_value' => 'float', 'useful_life_months' => 'integer',
    ];

    public function category(): BelongsTo { return $this->belongsTo(ErpFixedAssetCategory::class, 'asset_category_id'); }
    public function project(): BelongsTo { return $this->belongsTo(ErpProject::class, 'project_id'); }
    public function costCenter(): BelongsTo { return $this->belongsTo(ErpCostCenter::class, 'cost_center_id'); }
    public function depreciationLines(): HasMany { return $this->hasMany(ErpDepreciationLine::class, 'fixed_asset_id'); }
}
