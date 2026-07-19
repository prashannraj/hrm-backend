<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpCostCenter extends Model
{
    use HasFactory;

    protected $table = 'erp_cost_centers';

    protected $fillable = ['company_id', 'code', 'name', 'manager', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function projects(): HasMany { return $this->hasMany(ErpProject::class, 'cost_center_id'); }
}
