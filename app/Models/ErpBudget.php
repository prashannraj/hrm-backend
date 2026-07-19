<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpBudget extends Model
{
    use HasFactory;

    protected $table = 'erp_budgets';

    protected $fillable = ['company_id', 'fiscal_year_id', 'name', 'status', 'approved_by', 'approved_at'];
    protected $casts = ['approved_at' => 'datetime'];

    public function fiscalYear(): BelongsTo { return $this->belongsTo(ErpFiscalYear::class, 'fiscal_year_id'); }
    public function lines(): HasMany { return $this->hasMany(ErpBudgetLine::class, 'budget_id'); }
}
