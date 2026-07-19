<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpAuditEvent extends Model
{
    use HasFactory;

    protected $table = 'erp_audit_events';

    protected $fillable = [
        'company_id',
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
