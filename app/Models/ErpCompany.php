<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpCompany extends Model
{
    use HasFactory;

    protected $table = 'erp_companies';

    protected $fillable = [
        'name',
        'legal_name',
        'pan',
        'vat_number',
        'registration_number',
        'email',
        'phone',
        'address',
        'base_currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(ErpBranch::class, 'company_id');
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(ErpFiscalYear::class, 'company_id');
    }

    public function accountGroups(): HasMany
    {
        return $this->hasMany(ErpAccountGroup::class, 'company_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(ErpAccount::class, 'company_id');
    }
}
