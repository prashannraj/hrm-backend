<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpVoucherAttachment extends Model
{
    use HasFactory;

    protected $table = 'erp_voucher_attachments';

    protected $fillable = [
        'journal_voucher_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(ErpJournalVoucher::class, 'journal_voucher_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
