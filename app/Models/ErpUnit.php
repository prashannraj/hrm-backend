<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpUnit extends Model
{
    use HasFactory;

    protected $table = 'erp_units';

    protected $fillable = ['company_id', 'code', 'name', 'decimal_places', 'is_active'];

    protected $casts = ['decimal_places' => 'integer', 'is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ErpItem::class, 'unit_id');
    }
}
