<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpItemCategory extends Model
{
    use HasFactory;

    protected $table = 'erp_item_categories';

    protected $fillable = ['company_id', 'parent_id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ErpItem::class, 'item_category_id');
    }
}
