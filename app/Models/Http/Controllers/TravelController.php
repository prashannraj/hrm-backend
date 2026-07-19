<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class TravelController extends Controller
{
    /**
     * Get list of travel requests
     */
    public function index()
    {
        return response()->json(TravelRequest::orderBy('created_at', 'desc')->get());
    }

    /**
     * Submit a travel proposal/request
     */
    public function store(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
            'destination' => 'required|string',
            'purpose' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'estimatedCost' => 'required|numeric',
            'advanceAmount' => 'required|numeric',
        ]);

        $employeeId = $request->employeeId;
        $emp = Employee::find($employeeId);
        if (!$emp) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Custom ID generation (e.g. TRV-103)
        $latest = TravelRequest::where('id', 'LIKE', 'TRV-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 101;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'TRV-' . $nextNum;

        $travel = TravelRequest::create([
            'id' => $newId,
            'employeeId' => $employeeId,
            'employeeName' => $emp->name,
            'destination' => $request->destination,
            'purpose' => $request->purpose,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'estimatedCost' => (float)$request->estimatedCost,
            'advanceAmount' => (float)$request->advanceAmount,
            'status' => 'Pending',
            'expenses' => [],
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $emp->name,
            'action' => "Created Travel Proposal to {$request->destination}",
            'module' => 'TRAVEL'
        ]);

        return response()->json($travel, 201);
    }

    /**
     * Approve/Reject Travel request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'approver' => 'nullable|string',
            'action' => 'required|string|in:Approved,Rejected',
        ]);

        $travel = TravelRequest::find($id);
        if (!$travel) {
            return response()->json(['error' => 'Travel request not found'], 404);
        }

        $approver = $request->approver ?? 'Priya Patel';
        $status = $request->action === 'Rejected' ? 'Rejected' : 'Approved';

        $travel->update([
            'status' => $status,
            'approvedBy' => $approver,
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $approver,
            'action' => "{$status} travel request {$id} to {$travel->destination}",
            'module' => 'TRAVEL'
        ]);

        return response()->json($travel);
    }

    /**
     * Settle Travel Expenses (Actuals Claim)
     */
    public function settle(Request $request, $id)
    {
        $request->validate([
            'expenses' => 'required|array',
        ]);

        $travel = TravelRequest::find($id);
        if (!$travel) {
            return response()->json(['error' => 'Travel request not found'], 404);
        }

        $expenses = $request->expenses; // Array of {item: string, amount: number}
        $travel->update([
            'expenses' => $expenses,
            'status' => 'Settled',
        ]);

        $totalExpense = array_reduce($expenses, function ($sum, $item) {
            return $sum + (float)($item['amount'] ?? 0);
        }, 0);

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'System Finance',
            'action' => "Settled Travel Expenses for {$travel->employeeName} (Spent: NRs. {$totalExpense})",
            'module' => 'TRAVEL'
        ]);

        return response()->json($travel);
    }
}
