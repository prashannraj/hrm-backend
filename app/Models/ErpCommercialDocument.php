<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpCommercialDocument extends Model
{
    use HasFactory;

    protected $table = 'erp_commercial_documents';

    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'party_id', 'journal_voucher_id',
        'document_type', 'document_number', 'document_date_ad', 'document_date_bs', 'reference_number',
        'status', 'subtotal', 'discount_total', 'vat_total', 'tds_total', 'grand_total', 'remarks',
        'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'document_date_ad' => 'date',
        'posted_at' => 'datetime',
        'subtotal' => 'float',
        'discount_total' => 'float',
        'vat_total' => 'float',
        'tds_total' => 'float',
        'grand_total' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(ErpParty::class, 'party_id');
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ErpCommercialDocumentLine::class, 'commercial_document_id')->orderBy('line_order');
    }
}
