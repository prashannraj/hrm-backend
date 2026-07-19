<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    /**
     * Get list of policies
     */
    public function index()
    {
        return response()->json(Policy::orderBy('created_at', 'desc')->get());
    }

    /**
     * Publish a new policy
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        // Custom policy ID generation (e.g. POL-004)
        $latest = Policy::where('id', 'LIKE', 'POL-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'POL-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $policy = Policy::create([
            'id' => $newId,
            'title' => $request->title,
            'category' => $request->category ?? 'HR',
            'version' => $request->version ?? 'v1.0',
            'publishDate' => now()->toDateString(),
            'content' => $request->content,
            'acknowledgedBy' => [],
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'System Admin (HR)',
            'action' => "Published Policy: {$policy->title} ({$policy->version})",
            'module' => 'SETTINGS'
        ]);

        return response()->json($policy, 201);
    }

    /**
     * Acknowledge Policy by an Employee
     */
    public function acknowledge(Request $request, $id)
    {
        $request->validate([
            'employeeId' => 'required|string',
        ]);

        $policy = Policy::find($id);
        if (!$policy) {
            return response()->json(['error' => 'Policy not found.'], 404);
        }

        $employeeId = $request->employeeId;
        $ack = $policy->acknowledgedBy ?? [];

        if (!in_array($employeeId, $ack)) {
            $ack[] = $employeeId;
            $policy->update(['acknowledgedBy' => $ack]);

            $emp = Employee::find($employeeId);
            $employeeName = $emp ? $emp->name : "Employee {$employeeId}";

            AuditLog::create([
                'timestamp' => now(),
                'user' => $employeeName,
                'action' => "Acknowledged Policy: {$policy->title} ({$policy->version})",
                'module' => 'HRIS'
            ]);
        }

        return response()->json($policy);
    }
}
