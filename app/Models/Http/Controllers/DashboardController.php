<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Asset;
use App\Models\Vehicle;
use App\Models\LeaveRequest;
use App\Models\TravelRequest;
use App\Models\WfhRequest;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get real-time HRIS and MIS Statistics
     */
    public function stats()
    {
        $totalEmployees = Employee::count();
        $activeAssets = Asset::count();
        $maintenanceAssets = Asset::where('status', 'Maintenance')->count();
        $activeVehicles = Vehicle::where('status', 'Available')->count();
        $pendingLeaves = LeaveRequest::where('status', 'Pending')->count();
        $pendingTravels = TravelRequest::where('status', 'Pending')->count();
        $pendingWfh = WfhRequest::where('status', 'Pending')->count();

        // Attendance rate for today
        $todayStr = now()->toDateString();
        $presentToday = AttendanceLog::where('date', $todayStr)
            ->whereIn('status', ['Present', 'Late'])
            ->count();
        $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 100;

        // Expenditures
        $employees = Employee::all();
        $totalSalaries = $employees->sum(function ($emp) {
            return $emp->salaryBasic + $emp->salaryAllowances;
        });

        $assetValue = Asset::sum('cost');

        // Darbandi position control stats
        $darbandi = $this->getDarbandiStats($employees);

        return response()->json([
            'totalEmployees' => $totalEmployees,
            'activeAssets' => $activeAssets,
            'maintenanceAssets' => $maintenanceAssets,
            'activeVehicles' => $activeVehicles,
            'pendingLeaves' => $pendingLeaves,
            'pendingTravels' => $pendingTravels,
            'pendingWfh' => $pendingWfh,
            'attendanceRate' => $attendanceRate,
            'totalSalaries' => $totalSalaries,
            'assetValue' => $assetValue,
            'darbandi' => $darbandi
        ]);
    }

    /**
     * Darbandi Position Control Helper
     */
    private function getDarbandiStats($employees)
    {
        $approvedPositions = [
            ['title' => 'Executive Director', 'approved' => 1, 'department' => 'Executive'],
            ['title' => 'Finance & Admin Director', 'approved' => 1, 'department' => 'Finance'],
            ['title' => 'Senior Program Coordinator', 'approved' => 2, 'department' => 'Programs'],
            ['title' => 'M&E Specialist', 'approved' => 1, 'department' => 'M&E'],
            ['title' => 'HR Officer', 'approved' => 1, 'department' => 'Human Resources'],
            ['title' => 'Program Associate', 'approved' => 3, 'department' => 'Programs'],
            ['title' => 'Finance Assistant', 'approved' => 2, 'department' => 'Finance'],
            ['title' => 'Head Driver', 'approved' => 1, 'department' => 'Operations'],
            ['title' => 'Office Assistant', 'approved' => 2, 'department' => 'Operations']
        ];

        return array_map(function ($pos) use ($employees) {
            $filled = $employees->where('designation', $pos['title'])->count();
            return array_merge($pos, [
                'filled' => $filled,
                'remaining' => $pos['approved'] - $filled
            ]);
        }, $approvedPositions);
    }
}
