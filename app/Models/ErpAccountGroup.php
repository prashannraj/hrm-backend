<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpAccountGroup extends Model
{
    use HasFactory;

    protected $table = 'erp_account_groups';

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'normal_balance',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

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
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(ErpAccount::class, 'account_group_id');
    }
}
