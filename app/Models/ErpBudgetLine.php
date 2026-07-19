<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpBudgetLine extends Model
{
    use HasFactory;

    protected $table = 'erp_budget_lines';

    protected $fillable = ['budget_id', 'account_id', 'cost_center_id', 'project_id', 'amount', 'remarks'];
    protected $casts = ['amount' => 'float'];

    public function budget(): BelongsTo { return $this->belongsTo(ErpBudget::class, 'budget_id'); }
    public function account(): BelongsTo { return $this->belongsTo(ErpAccount::class, 'account_id'); }
    public function costCenter(): BelongsTo { return $this->belongsTo(ErpCostCenter::class, 'cost_center_id'); }
    public function project(): BelongsTo { return $this->belongsTo(ErpProject::class, 'project_id'); }
}
