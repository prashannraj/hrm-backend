<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Get list of leave requests
     */
    public function index()
    {
        return response()->json(LeaveRequest::orderBy('created_at', 'desc')->get());
    }

    /**
     * Submit a leave request
     */
    public function store(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
            'leaveType' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        $employeeId = $request->employeeId;
        $emp = Employee::find($employeeId);
        if (!$emp) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Custom ID generation (e.g. LV-103)
        $latest = LeaveRequest::where('id', 'LIKE', 'LV-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 101;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'LV-' . $nextNum;

        $leave = LeaveRequest::create([
            'id' => $newId,
            'employeeId' => $employeeId,
            'employeeName' => $emp->name,
            'leaveType' => $request->leaveType,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $emp->name,
            'action' => "Submitted Leave Request for {$request->leaveType} ({$request->startDate} to {$request->endDate})",
            'module' => 'LEAVE'
        ]);

        return response()->json($leave, 201);
    }

    /**
     * Approve/Reject a leave request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'approver' => 'nullable|string',
            'action' => 'required|string|in:Approved,Rejected',
        ]);

        $leave = LeaveRequest::find($id);
        if (!$leave) {
            return response()->json(['error' => 'Leave request not found'], 404);
        }

        $approver = $request->approver ?? 'Aarav Sharma';
        $status = $request->action === 'Rejected' ? 'Rejected' : 'Approved';

        $leave->update([
            'status' => $status,
            'approvedBy' => $approver,
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $approver,
            'action' => "{$status} leave request {$id} for {$leave->employeeName}",
            'module' => 'LEAVE'
        ]);

        return response()->json($leave);
    }
}
