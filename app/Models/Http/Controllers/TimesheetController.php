<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    /**
     * Get list of timesheets
     */
    public function index()
    {
        return response()->json(Timesheet::orderBy('date', 'desc')->get());
    }

    /**
     * Submit daily task timesheet
     */
    public function store(Request $request)
    {
        $request->validate([
            'employeeId' => 'required|string',
            'task' => 'required|string',
            'project' => 'required|string',
            'hours' => 'required|numeric',
        ]);

        $employeeId = $request->employeeId;
        $emp = Employee::find($employeeId);
        if (!$emp) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Custom ID generation (e.g. TS-103)
        $latest = Timesheet::where('id', 'LIKE', 'TS-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 101;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'TS-' . $nextNum;

        $timesheet = Timesheet::create([
            'id' => $newId,
            'employeeId' => $employeeId,
            'employeeName' => $emp->name,
            'date' => $request->date ?? now()->toDateString(),
            'task' => $request->task,
            'project' => $request->project,
            'hours' => (float)$request->hours,
            'status' => 'Submitted',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => $emp->name,
            'action' => "Submitted Daily Timesheet for project {$request->project} ({$request->hours} Hours)",
            'module' => 'TIMESHEET'
        ]);

        return response()->json($timesheet, 201);
    }
}
