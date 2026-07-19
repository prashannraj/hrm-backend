<?php

namespace App\Services\Commercial;

use App\Models\ErpCommercialDocument;
use App\Models\ErpCommercialDocumentSeries;
use App\Services\Accounting\JournalVoucherService;
use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;

class CommercialDocumentService
{
    public function __construct(
        private readonly JournalVoucherService $journalVoucherService,
        private readonly StockMovementService $stockMovementService
    ) {
    }

    public function create(array $data, ?int $userId = null): ErpCommercialDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $totals = $this->calculateTotals($data['lines']);
            $document = ErpCommercialDocument::create(array_merge($totals, [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'party_id' => $data['party_id'] ?? null,
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'] ?? $this->nextNumber($data),
                'document_date_ad' => $data['document_date_ad'],
                'document_date_bs' => $data['document_date_bs'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]));

            foreach (array_values($data['lines']) as $index => $line) {
                $lineTotals = $this->calculateLine($line);
                $document->lines()->create(array_merge($lineTotals, [
                    'item_id' => $line['item_id'] ?? null,
                    'account_id' => $line['account_id'] ?? null,
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'] ?? 0,
                    'rate' => $line['rate'] ?? 0,
                    'discount_rate' => $line['discount_rate'] ?? 0,
                    'vat_rate' => $line['vat_rate'] ?? 0,
                    'tds_rate' => $line['tds_rate'] ?? 0,
                    'line_order' => $index + 1,
                ]));
            }

            if (($data['post_now'] ?? false) === true) {
                $document = $this->post($document, $data, $userId);
            }

            return $document->load(['party', 'lines.item', 'lines.account']);
        });
    }

    public function post(ErpCommercialDocument $document, array $context = [], ?int $userId = null): ErpCommercialDocument
    {
        if ($document->status === 'posted') {
            return $document->load(['party', 'lines.item', 'lines.account']);
        }

        return DB::transaction(function () use ($document, $context, $userId) {
            $document->load(['party.account', 'lines.item', 'lines.account']);
            $voucher = $this->journalVoucherService->create($this->voucherPayload($document), $userId);
            $this->journalVoucherService->post($voucher, $userId);

            if (in_array($document->document_type, ['purchase_bill', 'sales_invoice', 'purchase_return', 'sales_return'], true)) {
                $this->stockMovementService->post($this->stockMovementService->create($this->stockPayload($document, $context), $userId), $userId);
            }

            $document->update([
                'status' => 'posted',
                'journal_voucher_id' => $voucher->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $document->refresh()->load(['party', 'lines.item', 'journalVoucher']);
        });
    }

    public function outstanding(int $companyId, string $partyType)
    {
        return ErpCommercialDocument::query()
            ->with('party')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereHas('party', fn ($query) => $query->where('party_type', $partyType))
            ->selectRaw('party_id, SUM(grand_total) as amount')
            ->groupBy('party_id')
            ->get();
    }

    private function calculateTotals(array $lines): array
    {
        return array_reduce($lines, function ($carry, $line) {
            $lineTotals = $this->calculateLine($line);
            $carry['subtotal'] += (float) ($line['quantity'] ?? 0) * (float) ($line['rate'] ?? 0);
            $carry['discount_total'] += $lineTotals['discount_amount'];
            $carry['vat_total'] += $lineTotals['vat_amount'];
            $carry['tds_total'] += $lineTotals['tds_amount'];
            $carry['grand_total'] += $lineTotals['line_total'];
            return $carry;
        }, ['subtotal' => 0, 'discount_total' => 0, 'vat_total' => 0, 'tds_total' => 0, 'grand_total' => 0]);
    }

    private function calculateLine(array $line): array
    {
        $gross = (float) ($line['quantity'] ?? 0) * (float) ($line['rate'] ?? 0);
        $discount = $gross * (float) ($line['discount_rate'] ?? 0) / 100;
        $taxable = $gross - $discount;
        $vat = $taxable * (float) ($line['vat_rate'] ?? 0) / 100;
        $tds = $taxable * (float) ($line['tds_rate'] ?? 0) / 100;

        return [
            'discount_amount' => $discount,
            'vat_amount' => $vat,
            'tds_amount' => $tds,
            'line_total' => $taxable + $vat - $tds,
        ];
    }

    private function nextNumber(array $data): string
    {
        $series = ErpCommercialDocumentSeries::firstOrCreate([
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'fiscal_year_id' => $data['fiscal_year_id'],
            'document_type' => $data['document_type'],
        ], ['prefix' => strtoupper(substr($data['document_type'], 0, 3)), 'next_number' => 1, 'padding' => 5]);

        $number = $series->prefix . '-' . str_pad((string) $series->next_number, $series->padding, '0', STR_PAD_LEFT);
        $series->increment('next_number');

        return $number;
    }

    private function voucherPayload(ErpCommercialDocument $document): array
    {
        $partyAccountId = $document->party?->account_id ?? $document->lines->first()?->account_id;
        $lineAccountId = $document->lines->first()?->account_id ?? $partyAccountId;
        $isSale = in_array($document->document_type, ['sales_invoice', 'sales_return'], true);

        return [
            'company_id' => $document->company_id,
            'branch_id' => $document->branch_id,
            'fiscal_year_id' => $document->fiscal_year_id,
            'voucher_type' => $isSale ? 'sales' : 'purchase',
            'voucher_date_ad' => $document->document_date_ad->format('Y-m-d'),
            'narration' => $document->document_number,
            'lines' => [
                ['account_id' => $partyAccountId, 'debit' => $isSale ? $document->grand_total : 0, 'credit' => $isSale ? 0 : $document->grand_total],
                ['account_id' => $lineAccountId, 'debit' => $isSale ? 0 : $document->grand_total, 'credit' => $isSale ? $document->grand_total : 0],
            ],
        ];
    }

    private function stockPayload(ErpCommercialDocument $document, array $context): array
    {
        $isStockIn = in_array($document->document_type, ['purchase_bill', 'sales_return'], true);

        return [
            'company_id' => $document->company_id,
            'branch_id' => $document->branch_id,
            'fiscal_year_id' => $document->fiscal_year_id,
            'movement_type' => $isStockIn ? 'purchase_receipt' : 'sales_issue',
            'movement_date_ad' => $document->document_date_ad->format('Y-m-d'),
            'reference_type' => 'commercial_document',
            'reference_id' => $document->id,
            'lines' => $document->lines->filter(fn ($line) => $line->item_id)->map(fn ($line) => [
                'item_id' => $line->item_id,
                'warehouse_id' => $line->warehouse_id ?? ($context['warehouse_id'] ?? null),
                'quantity_in' => $isStockIn ? $line->quantity : 0,
                'quantity_out' => $isStockIn ? 0 : $line->quantity,
                'unit_cost' => $line->rate,
            ])->values()->all(),
        ];
    }
}
