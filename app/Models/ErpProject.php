<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpProject extends Model
{
    use HasFactory;

    protected $table = 'erp_projects';

    protected $fillable = ['company_id', 'cost_center_id', 'code', 'name', 'starts_on', 'ends_on', 'contract_value', 'status'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'contract_value' => 'float'];

    public function costCenter(): BelongsTo { return $this->belongsTo(ErpCostCenter::class, 'cost_center_id'); }
}
