<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpBillingProfile extends Model
{
    use HasFactory;

    protected $table = 'erp_billing_profiles';

    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'profile_type', 'display_name',
        'series_prefix', 'next_number', 'padding', 'print_layout', 'requires_vat', 'is_active',
    ];

    protected $casts = [
        'requires_vat' => 'boolean',
        'is_active' => 'boolean',
    ];

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
