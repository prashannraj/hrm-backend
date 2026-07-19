<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpTaxInvoiceAudit extends Model
{
    use HasFactory;

    protected $table = 'erp_tax_invoice_audits';

    protected $fillable = [
        'company_id', 'commercial_document_id', 'audit_type', 'severity', 'message', 'metadata', 'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ErpCommercialDocument::class, 'commercial_document_id');
    }
}
