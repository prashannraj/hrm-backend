<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Get list of assets
     */
    public function index()
    {
        return response()->json(Asset::orderBy('created_at', 'desc')->get());
    }

    /**
     * Register a new asset
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string|in:IT,Furniture,Vehicle,Equipment',
            'cost' => 'required|numeric',
            'assignedTo' => 'nullable|string',
        ]);

        $category = $request->category;
        $assignedTo = $request->assignedTo;

        // Custom auto-id (AST-006)
        $latest = Asset::where('id', 'LIKE', 'AST-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        if ($latest) {
            $parts = explode('-', $latest->id);
            $nextNum = (int)$parts[1] + 1;
        }
        $newId = 'AST-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        // Code generation: AST-IT-2026-6
        $codeCategory = strtoupper(substr($category, 0, 3));
        $assetCode = "AST-{$codeCategory}-2026-{$nextNum}";

        $asset = Asset::create([
            'id' => $newId,
            'code' => $assetCode,
            'name' => $request->name,
            'category' => $category,
            'assignedTo' => $assignedTo ?: null,
            'purchaseDate' => $request->purchaseDate ?? now()->toDateString(),
            'cost' => (float)$request->cost,
            'status' => 'Active',
            'maintenanceLogs' => [],
        ]);

        // Bidirectional Assignment Sync to Employee
        if ($assignedTo) {
            $emp = Employee::find($assignedTo);
            if ($emp) {
                $empAssets = $emp->assignedAssets ?? [];
                if (!in_array($newId, $empAssets)) {
                    $empAssets[] = $newId;
                    $emp->update(['assignedAssets' => $empAssets]);
                }
            }
        }

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'Admin/Assets Officer',
            'action' => "Registered New Asset {$assetCode} ({$request->name})",
            'module' => 'ASSETS'
        ]);

        return response()->json($asset, 201);
    }

    /**
     * Move asset to maintenance
     */
    public function logMaintenance(Request $request, $id)
    {
        $request->validate([
            'cost' => 'required|numeric',
            'description' => 'required|string',
        ]);

        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        $logs = $asset->maintenanceLogs ?? [];
        array_unshift($logs, [
            'date' => now()->toDateString(),
            'cost' => (float)$request->cost,
            'description' => $request->description,
        ]);

        $asset->update([
            'status' => 'Maintenance',
            'maintenanceLogs' => $logs,
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'Asset Maintenance Team',
            'action' => "Moved asset {$asset->code} to maintenance ({$request->description})",
            'module' => 'ASSETS'
        ]);

        return response()->json($asset);
    }

    /**
     * Resolve asset maintenance back to Active
     */
    public function resolveMaintenance($id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        $asset->update([
            'status' => 'Active',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'Asset Maintenance Team',
            'action' => "Resolved maintenance for asset {$asset->code} to Active",
            'module' => 'ASSETS'
        ]);

        return response()->json($asset);
    }
}
