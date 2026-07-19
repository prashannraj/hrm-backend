<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpFixedAssetCategory extends Model
{
    use HasFactory;

    protected $table = 'erp_fixed_asset_categories';

    protected $fillable = [
        'company_id', 'code', 'name', 'asset_account_id', 'depreciation_expense_account_id',
        'accumulated_depreciation_account_id', 'default_method', 'default_rate', 'is_active',
    ];

    protected $casts = ['default_rate' => 'float', 'is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(ErpCompany::class, 'company_id'); }
    public function assets(): HasMany { return $this->hasMany(ErpFixedAsset::class, 'asset_category_id'); }
}
