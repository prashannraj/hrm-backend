<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpParty extends Model
{
    use HasFactory;

    protected $table = 'erp_parties';

    protected $fillable = [
        'company_id', 'account_id', 'party_type', 'code', 'name', 'pan', 'vat_number',
        'email', 'phone', 'billing_address', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ErpAccount::class, 'account_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ErpCommercialDocument::class, 'party_id');
    }
}
