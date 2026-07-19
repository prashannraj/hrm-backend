<?php

namespace App\Http\Controllers;

use App\Models\WfhRequest;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class WfhController extends Controller
{
    /**
     * Get list of WFH requests
     */
    public function index()
    {
        return response()->json(WfhRequest::orderBy('created_at', 'desc')->get());
    }

    /**
     * Submit a WFH request
     */
    public function store(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        $employeeId = $request->employeeId;
        $emp = Employee::find($employeeId);
        if (!$emp) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Custom ID generation (e.g. WFH-003)
        $latest = WfhRequest::where('id', 'LIKE', 'WFH-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'WFH-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $wfh = WfhRequest::create([
            'id' => $newId,
            'employeeId' => $employeeId,
            'employeeName' => $emp->name,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $emp->name,
            'action' => "Submitted WFH Request ({$request->startDate} to {$request->endDate})",
            'module' => 'WFH'
        ]);

        return response()->json($wfh, 201);
    }

    /**
     * Approve/Reject WFH request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'approver' => 'nullable|string',
            'action' => 'required|string|in:Approved,Rejected',
        ]);

        $wfh = WfhRequest::find($id);
        if (!$wfh) {
            return response()->json(['error' => 'WFH request not found'], 404);
        }

        $approver = $request->approver ?? 'Aarav Sharma';
        $status = $request->action === 'Rejected' ? 'Rejected' : 'Approved';

        $wfh->update([
            'status' => $status,
            'approvedBy' => $approver,
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $approver,
            'action' => "{$status} WFH request {$id} for {$wfh->employeeName}",
            'module' => 'WFH'
        ]);

        return response()->json($wfh);
    }
}
