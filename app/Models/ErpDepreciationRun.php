<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpDepreciationRun extends Model
{
    use HasFactory;

    protected $table = 'erp_depreciation_runs';

    protected $fillable = ['company_id', 'fiscal_year_id', 'period_from', 'period_to', 'status', 'total_depreciation', 'journal_voucher_id', 'created_by'];
    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'total_depreciation' => 'float'];

    public function lines(): HasMany { return $this->hasMany(ErpDepreciationLine::class, 'depreciation_run_id'); }
    public function journalVoucher(): BelongsTo { return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id'); }
}
