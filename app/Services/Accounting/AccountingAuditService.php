<?php

namespace App\Services\Accounting;

use App\Models\ErpAuditEvent;
use Illuminate\Http\Request;

class AccountingAuditService
{
    public function record(string $eventType, ?int $companyId = null, mixed $auditable = null, array $payload = [], ?Request $request = null): void
    {
        ErpAuditEvent::create([
            'company_id' => $companyId,
            'user_id' => $request?->user()?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'payload' => $payload,
            'ip_address' => $request?->ip(),
        ]);
    }
}
