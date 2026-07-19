<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpVoucherApproval extends Model
{
    use HasFactory;

    protected $table = 'erp_voucher_approvals';

    protected $fillable = [
        'journal_voucher_id',
        'requested_by',
        'approved_by',
        'status',
        'remarks',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
