<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    /**
     * Get list of vehicles
     */
    public function index()
    {
        return response()->json(Vehicle::orderBy('created_at', 'desc')->get());
    }

    /**
     * Store logs (Fuel or Trips)
     */
    public function storeLog(Request $request)
    {
        $request->validate([
            'vehicleId' => 'required|string',
            'type' => 'required|string|in:fuel,trip',
        ]);

        $vehicle = Vehicle::find($request->vehicleId);
        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        $logDate = $request->date ?? now()->toDateString();

        if ($request->type === 'fuel') {
            $fuelLogs = $vehicle->fuelLogs ?? [];
            array_unshift($fuelLogs, [
                'date' => $logDate,
                'liters' => (float)($request->liters ?? 0),
                'cost' => (float)($request->cost ?? 0),
                'mileage' => (float)($request->mileage ?? 0),
            ]);

            $vehicle->update(['fuelLogs' => $fuelLogs]);

            AuditLog::create([
                'timestamp' => now(),
                'user' => $vehicle->driverName,
                'action' => "Logged {$request->liters}L fuel purchase for vehicle {$vehicle->plateNumber}",
                'module' => 'FLEET'
            ]);

        } elseif ($request->type === 'trip') {
            $trips = $vehicle->trips ?? [];
            array_unshift($trips, [
                'date' => $logDate,
                'route' => $request->route ?? '',
                'purpose' => $request->purpose ?? '',
                'miles' => (float)($request->miles ?? 0),
            ]);

            $vehicle->update(['trips' => $trips]);

            AuditLog::create([
                'timestamp' => now(),
                'user' => $vehicle->driverName,
                'action' => "Logged trip to {$request->route} ({$request->miles} Miles)",
                'module' => 'FLEET'
            ]);
        }

        return response()->json($vehicle);
    }
}
