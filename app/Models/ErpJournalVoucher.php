<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpJournalVoucher extends Model
{
    use HasFactory;

    protected $table = 'erp_journal_vouchers';

    protected $fillable = [
        'company_id',
        'branch_id',
        'fiscal_year_id',
        'voucher_type',
        'voucher_number',
        'voucher_date_ad',
        'voucher_date_bs',
        'narration',
        'party_name',
        'reference_number',
        'reference_date_ad',
        'reference_date_bs',
        'status',
        'total_debit',
        'total_credit',
        'posted_at',
        'created_by',
        'posted_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'voucher_date_ad' => 'date',
        'reference_date_ad' => 'date',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_debit' => 'float',
        'total_credit' => 'float',
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

    public function lines(): HasMany
    {
        return $this->hasMany(ErpJournalLine::class, 'journal_voucher_id')->orderBy('line_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ErpVoucherApproval::class, 'journal_voucher_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ErpVoucherAttachment::class, 'journal_voucher_id');
    }
}
