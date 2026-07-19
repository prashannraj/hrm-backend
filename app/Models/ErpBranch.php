<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpBranch extends Model
{
    use HasFactory;

    protected $table = 'erp_branches';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'pan',
        'vat_number',
        'address',
        'is_head_office',
        'is_active',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function journalVouchers(): HasMany
    {
        return $this->hasMany(ErpJournalVoucher::class, 'branch_id');
    }
}
