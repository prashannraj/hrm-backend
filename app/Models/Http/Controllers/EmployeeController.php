<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Asset;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Employee::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Auto-increment Employee ID Custom format (e.g., EMP-006)
        $latest = Employee::where('id', 'LIKE', 'EMP-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'EMP-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $employee = Employee::create([
            'id' => $newId,
            'name' => $request->name ?? 'New Employee',
            'gender' => $request->gender ?? 'Male',
            'dob' => $request->dob ?? '1995-01-01',
            'maritalStatus' => $request->maritalStatus ?? 'Single',
            'phone' => $request->phone ?? '',
            'email' => $request->email ?? '',
            'address' => $request->address ?? '',
            'emergencyContact' => $request->emergencyContact ?? '',
            'citizenshipNo' => $request->citizenshipNo ?? 'C-TEMP-9920',
            'passportNo' => $request->passportNo ?? '',
            'joinDate' => $request->joinDate ?? now()->toDateString(),
            'probationMonths' => (int)($request->probationMonths ?? 3),
            'contractType' => $request->contractType ?? 'Contractual',
            'department' => $request->department ?? 'Programs',
            'designation' => $request->designation ?? 'Program Associate',
            'salaryBasic' => (float)($request->salaryBasic ?? 50000),
            'salaryAllowances' => (float)($request->salaryAllowances ?? 8000),
            'salaryDeductions' => (float)($request->salaryDeductions ?? 5000),
            'pan' => $request->pan ?? '',
            'ssf' => $request->ssf ?? '',
            'cit' => $request->cit ?? '',
            'taxInfo' => $request->taxInfo ?? '10% Bracket',
            'assignedAssets' => $request->assignedAssets ?? [],
            'education' => $request->education ?? "Bachelor's Degree",
            'experience' => $request->experience ?? "Fresh entry / mid level experience",
            'dependents' => $request->dependents ?? "None",
            'profilePicture' => $request->profilePicture ?? "",
            'documents' => $request->documents ?? [],
            'allowancesList' => $request->allowancesList ?? [],
            'deductionsList' => $request->deductionsList ?? [],
            'lifecycleHistory' => $request->lifecycleHistory ?? [],
        ]);

        // Log System Audit Action
        AuditLog::create([
            'timestamp' => now(),
            'user' => 'System Admin (HR)',
            'action' => "Created Employee Record {$employee->id} ({$employee->name})",
            'module' => 'HRIS'
        ]);

        return response()->json($employee, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        return response()->json($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $data = $request->all();

        // Enforce types
        if (isset($data['salaryBasic'])) $data['salaryBasic'] = (float)$data['salaryBasic'];
        if (isset($data['salaryAllowances'])) $data['salaryAllowances'] = (float)$data['salaryAllowances'];
        if (isset($data['salaryDeductions'])) $data['salaryDeductions'] = (float)$data['salaryDeductions'];
        if (isset($data['probationMonths'])) $data['probationMonths'] = (int)$data['probationMonths'];

        // Bidirectional asset assignment linkage sync
        if ($request->has('assignedAssets')) {
            $newAssetIds = $request->assignedAssets ?? [];
            $allAssets = Asset::all();

            foreach ($allAssets as $asset) {
                if ($asset->assignedTo === $id && !in_array($asset->id, $newAssetIds)) {
                    $asset->update(['assignedTo' => null]);
                } elseif (in_array($asset->id, $newAssetIds) && $asset->assignedTo !== $id) {
                    $asset->update(['assignedTo' => $id]);
                }
            }
        }

        $employee->update($data);

        // Audit Logging
        AuditLog::create([
            'timestamp' => now(),
            'user' => 'System Admin (HR)',
            'action' => "Updated Employee Record {$id} ({$employee->name})",
            'module' => 'HRIS'
        ]);

        return response()->json($employee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $employeeName = $employee->name;
        $employee->delete();

        // Audit Logging
        AuditLog::create([
            'timestamp' => now(),
            'user' => 'System Admin (HR)',
            'action' => "Archived Employee Record {$id} ({$employeeName})",
            'module' => 'HRIS'
        ]);

        return response()->json(['success' => true, 'message' => "Employee {$id} archived"]);
    }
}
