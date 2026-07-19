<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Get listing of all attendance logs
     */
    public function index()
    {
        return response()->json(AttendanceLog::orderBy('date', 'desc')->get());
    }

    /**
     * Check In Handler
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
        ]);

        $employeeId = $request->employeeId;
        $emp = Employee::find($employeeId);
        if (!$emp) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $todayStr = now()->toDateString();
        $existing = AttendanceLog::where('employeeId', $employeeId)
            ->where('date', $todayStr)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Already checked in today'], 400);
        }

        $checkInTime = now()->format('H:i'); // "09:15"
        $standardHour = 9;

        $timeParts = explode(':', $checkInTime);
        $h = (int)$timeParts[0];
        $m = (int)$timeParts[1];

        // Lateness calculation
        $lateMinutes = 0;
        if ($h > $standardHour) {
            $lateMinutes = ($h - $standardHour) * 60 + $m;
        } elseif ($h === $standardHour && $m > 0) {
            $lateMinutes = $m;
        }

        $status = $lateMinutes > 15 ? 'Late' : 'Present';

        // Auto ID generation (e.g. ATT-001)
        $latest = AttendanceLog::where('id', 'LIKE', 'ATT-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'ATT-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $log = AttendanceLog::create([
            'id' => $newId,
            'employeeId' => $employeeId,
            'employeeName' => $emp->name,
            'date' => $todayStr,
            'checkIn' => $checkInTime,
            'status' => $status,
            'overtimeMinutes' => 0,
            'lateMinutes' => $lateMinutes,
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $emp->name,
            'action' => "Checked In at {$checkInTime} (Status: {$status})",
            'module' => 'ATTENDANCE'
        ]);

        return response()->json($log, 201);
    }

    /**
     * Check Out Handler
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
        ]);

        $employeeId = $request->employeeId;
        $todayStr = now()->toDateString();

        $log = AttendanceLog::where('employeeId', $employeeId)
            ->where('date', $todayStr)
            ->first();

        if (!$log) {
            return response()->json(['error' => 'Check-in record not found for today'], 404);
        }

        if ($log->checkOut) {
            return response()->json(['error' => 'Already checked out today'], 400);
        }

        $checkOutTime = now()->format('H:i'); // "17:30"
        $log->checkOut = $checkOutTime;

        // Calculate overtime if staying past 17:00
        $timeParts = explode(':', $checkOutTime);
        $h = (int)$timeParts[0];
        $m = (int)$timeParts[1];

        if ($h >= 17) {
            $log->overtimeMinutes = ($h - 17) * 60 + $m;
        }

        $log->save();

        AuditLog::create([
            'timestamp' => now(),
            'user' => $log->employeeName,
            'action' => "Checked Out at {$checkOutTime} (Overtime: {$log->overtimeMinutes} mins)",
            'module' => 'ATTENDANCE'
        ]);

        return response()->json($log);
    }
}
