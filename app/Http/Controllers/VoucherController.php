<?php

namespace App\Http\Controllers;

use App\Models\ErpJournalVoucher;
use App\Services\Accounting\VoucherOperationService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherOperationService $voucherOperationService
    ) {
    }

    public function series(Request $request)
    {
        return response()->json($this->voucherOperationService->seriesFor($this->context($request)));
    }

    public function submit(Request $request, ErpJournalVoucher $voucher)
    {
        $approval = $this->voucherOperationService->submitApproval($voucher, $request->user()?->id, $request->string('remarks')->toString());

        return response()->json($approval->load('voucher'), 201);
    }

    public function approve(Request $request, ErpJournalVoucher $voucher)
    {
        $approval = $this->voucherOperationService->approve($voucher, $request->user()?->id, $request->string('remarks')->toString());

        return response()->json($approval->load('voucher'));
    }

    public function cancel(Request $request, ErpJournalVoucher $voucher)
    {
        $voucher = $this->voucherOperationService->cancel($voucher, $request->user()?->id, $request->string('reason')->toString());

        return response()->json($voucher);
    }

    public function attach(Request $request, ErpJournalVoucher $voucher)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'description' => ['nullable', 'string'],
        ]);

        $attachment = $this->voucherOperationService->attachFile($voucher, $request->file('file'), $request->user()?->id, $request->input('description'));

        return response()->json($attachment, 201);
    }

    public function reverse(Request $request, ErpJournalVoucher $voucher)
    {
        $reversal = $this->voucherOperationService->reverse($voucher, $request->user()?->id, $request->string('reason')->toString());

        return response()->json($reversal);
    }

    private function context(Request $request): array
    {
        return [
            'company_id' => (int) $request->input('company_id'),
            'branch_id' => (int) $request->input('branch_id'),
            'fiscal_year_id' => (int) $request->input('fiscal_year_id'),
            'voucher_type' => $request->input('voucher_type', 'journal'),
        ];
    }
}
