<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpCommercialDocumentSeries extends Model
{
    use HasFactory;

    protected $table = 'erp_commercial_document_series';

    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'document_type', 'prefix',
        'next_number', 'padding', 'is_active',
    ];

    protected $casts = ['next_number' => 'integer', 'padding' => 'integer', 'is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ErpBranch::class, 'branch_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(ErpFiscalYear::class, 'fiscal_year_id');
    }
}
