<?php

namespace App\Services\Billing;

use App\Models\ErpBillingProfile;
use App\Models\ErpCommercialDocument;
use App\Models\ErpTaxInvoiceAudit;
use App\Models\ErpTaxPeriodReport;
use App\Models\ErpTaxRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NepalTaxComplianceService
{
    public function dashboard(int $companyId): array
    {
        $salesVat = ErpCommercialDocument::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereIn('document_type', ['sales_invoice', 'sales_return'])
            ->sum('vat_total');
        $purchaseVat = ErpCommercialDocument::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereIn('document_type', ['purchase_bill', 'purchase_return'])
            ->sum('vat_total');

        return [
            'vat_payable' => round($salesVat - $purchaseVat, 2),
            'sales_vat' => round($salesVat, 2),
            'purchase_vat' => round($purchaseVat, 2),
            'tds_deducted' => round(ErpCommercialDocument::where('company_id', $companyId)->where('status', 'posted')->sum('tds_total'), 2),
            'invoice_gaps' => $this->invoiceGaps($companyId)->count(),
            'audit_warnings' => ErpTaxInvoiceAudit::where('company_id', $companyId)->whereIn('severity', ['warning', 'critical'])->count(),
        ];
    }

    public function createRate(array $data): ErpTaxRate
    {
        return ErpTaxRate::create($data);
    }

    public function createProfile(array $data): ErpBillingProfile
    {
        return ErpBillingProfile::create($data);
    }

    public function issueTaxInvoice(ErpCommercialDocument $document, array $context, ?int $userId = null): ErpCommercialDocument
    {
        if ($document->status === 'posted') {
            throw ValidationException::withMessages(['document' => 'Posted tax invoices are immutable. Use return, credit note, or cancellation workflows.']);
        }

        if (!in_array($document->document_type, ['sales_invoice', 'sales_return'], true)) {
            throw ValidationException::withMessages(['document_type' => 'Only sales invoices and sales returns can be issued as tax documents.']);
        }

        $document->load(['party', 'lines']);
        $this->assertVatRegistration($document);
        $this->assertPanFormat($document->party?->pan);

        return DB::transaction(function () use ($document, $context, $userId) {
            $profile = $this->profileFor($context, $document->vat_total > 0 ? 'tax_invoice' : 'abbreviated_tax_invoice');
            $document->update([
                'document_number' => $this->nextInvoiceNumber($profile),
                'reference_number' => $document->reference_number ?: strtoupper($profile->profile_type),
            ]);

            ErpTaxInvoiceAudit::create([
                'company_id' => $document->company_id,
                'commercial_document_id' => $document->id,
                'audit_type' => 'tax_invoice_issued',
                'severity' => 'info',
                'message' => 'Tax invoice number assigned and locked for posting.',
                'metadata' => ['document_number' => $document->document_number, 'profile_type' => $profile->profile_type],
                'created_by' => $userId,
            ]);

            return $document->refresh()->load(['party', 'lines.item']);
        });
    }

    public function vatReport(array $context, string $from, string $to): ErpTaxPeriodReport
    {
        $sales = $this->documentTaxSummary($context['company_id'], ['sales_invoice'], $from, $to);
        $salesReturns = $this->documentTaxSummary($context['company_id'], ['sales_return'], $from, $to);
        $purchases = $this->documentTaxSummary($context['company_id'], ['purchase_bill'], $from, $to);
        $purchaseReturns = $this->documentTaxSummary($context['company_id'], ['purchase_return'], $from, $to);

        $taxableSales = $sales['taxable'] - $salesReturns['taxable'];
        $salesVat = $sales['vat'] - $salesReturns['vat'];
        $taxablePurchases = $purchases['taxable'] - $purchaseReturns['taxable'];
        $purchaseVat = $purchases['vat'] - $purchaseReturns['vat'];

        return ErpTaxPeriodReport::create([
            'company_id' => $context['company_id'],
            'fiscal_year_id' => $context['fiscal_year_id'],
            'report_type' => 'vat',
            'period_from' => $from,
            'period_to' => $to,
            'taxable_sales' => $taxableSales,
            'sales_vat' => $salesVat,
            'taxable_purchases' => $taxablePurchases,
            'purchase_vat' => $purchaseVat,
            'net_payable' => $salesVat - $purchaseVat,
            'metadata' => compact('sales', 'salesReturns', 'purchases', 'purchaseReturns'),
        ]);
    }

    public function tdsReport(array $context, string $from, string $to): ErpTaxPeriodReport
    {
        $tds = ErpCommercialDocument::where('company_id', $context['company_id'])
            ->where('status', 'posted')
            ->whereBetween('document_date_ad', [$from, $to])
            ->sum('tds_total');

        return ErpTaxPeriodReport::create([
            'company_id' => $context['company_id'],
            'fiscal_year_id' => $context['fiscal_year_id'],
            'report_type' => 'tds',
            'period_from' => $from,
            'period_to' => $to,
            'tds_deducted' => $tds,
            'net_payable' => $tds,
            'metadata' => ['basis' => 'posted commercial documents'],
        ]);
    }

    public function invoiceGaps(int $companyId)
    {
        return ErpTaxInvoiceAudit::where('company_id', $companyId)
            ->where('audit_type', 'invoice_gap')
            ->latest()
            ->get();
    }

    public function auditDocuments(int $companyId, ?int $userId = null): int
    {
        $created = 0;
        $documents = ErpCommercialDocument::where('company_id', $companyId)
            ->whereIn('document_type', ['sales_invoice', 'sales_return'])
            ->orderBy('document_number')
            ->get();

        foreach ($documents as $document) {
            if ($document->vat_total > 0 && !$document->party?->pan) {
                ErpTaxInvoiceAudit::firstOrCreate([
                    'company_id' => $companyId,
                    'commercial_document_id' => $document->id,
                    'audit_type' => 'missing_party_pan',
                ], [
                    'severity' => 'warning',
                    'message' => 'Tax invoice party PAN is missing.',
                    'created_by' => $userId,
                ]);
                $created++;
            }
        }

        return $created;
    }

    private function profileFor(array $context, string $profileType): ErpBillingProfile
    {
        return ErpBillingProfile::firstOrCreate([
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'fiscal_year_id' => $context['fiscal_year_id'],
            'profile_type' => $profileType,
        ], [
            'display_name' => str_replace('_', ' ', strtoupper($profileType)),
            'series_prefix' => $profileType === 'tax_invoice' ? 'TI' : 'ABI',
            'next_number' => 1,
            'padding' => 6,
            'print_layout' => 'a4',
            'requires_vat' => $profileType === 'tax_invoice',
        ]);
    }

    private function nextInvoiceNumber(ErpBillingProfile $profile): string
    {
        $number = $profile->series_prefix . '-' . str_pad((string) $profile->next_number, $profile->padding, '0', STR_PAD_LEFT);
        $profile->increment('next_number');

        return $number;
    }

    private function assertVatRegistration(ErpCommercialDocument $document): void
    {
        if ($document->vat_total <= 0) {
            return;
        }

        if (!$document->company?->vat_number && !$document->company?->pan) {
            throw ValidationException::withMessages(['company' => 'Company PAN or VAT number is required before issuing a VAT invoice.']);
        }
    }

    private function assertPanFormat(?string $pan): void
    {
        if ($pan && !preg_match('/^\d{9}$/', $pan)) {
            throw ValidationException::withMessages(['pan' => 'PAN must be a 9 digit Nepal PAN number.']);
        }
    }

    private function documentTaxSummary(int $companyId, array $types, string $from, string $to): array
    {
        $rows = ErpCommercialDocument::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereIn('document_type', $types)
            ->whereBetween('document_date_ad', [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()])
            ->get();

        return [
            'taxable' => round($rows->sum(fn ($document) => $document->subtotal - $document->discount_total), 2),
            'vat' => round($rows->sum('vat_total'), 2),
        ];
    }
}
