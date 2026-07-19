<?php

namespace App\Services\Inventory;

use App\Models\ErpItem;
use App\Models\ErpStockMovement;
use App\Models\ErpStockMovementLine;
use App\Models\ErpStockValuationLayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function create(array $data, ?int $userId = null): ErpStockMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            $movement = ErpStockMovement::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'movement_type' => $data['movement_type'],
                'movement_number' => $data['movement_number'] ?? $this->nextMovementNumber($data['company_id'], $data['movement_type']),
                'movement_date_ad' => $data['movement_date_ad'],
                'movement_date_bs' => $data['movement_date_bs'] ?? null,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $line) {
                $quantityIn = (float) ($line['quantity_in'] ?? 0);
                $quantityOut = (float) ($line['quantity_out'] ?? 0);

                if ($quantityIn <= 0 && $quantityOut <= 0) {
                    throw ValidationException::withMessages(['lines' => 'Each stock line must have quantity in or quantity out.']);
                }

                if ($quantityIn > 0 && $quantityOut > 0) {
                    throw ValidationException::withMessages(['lines' => 'A stock line cannot have both quantity in and quantity out.']);
                }

                $movement->lines()->create([
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'to_warehouse_id' => $line['to_warehouse_id'] ?? null,
                    'batch_id' => $line['batch_id'] ?? null,
                    'quantity_in' => $quantityIn,
                    'quantity_out' => $quantityOut,
                    'unit_cost' => (float) ($line['unit_cost'] ?? 0),
                    'total_cost' => ($quantityIn ?: $quantityOut) * (float) ($line['unit_cost'] ?? 0),
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $movement->load(['lines.item', 'lines.warehouse']);
        });
    }

    public function post(ErpStockMovement $movement, ?int $userId = null): ErpStockMovement
    {
        if ($movement->status === 'posted') {
            return $movement->load(['lines.item', 'lines.warehouse']);
        }

        return DB::transaction(function () use ($movement, $userId) {
            $movement->load(['lines.item']);

            foreach ($movement->lines as $line) {
                $item = $line->item;

                if ($line->quantity_out > 0) {
                    $available = $this->availableQuantity($line->item_id, $line->warehouse_id);
                    if ($available < $line->quantity_out) {
                        throw ValidationException::withMessages(['stock' => "Insufficient stock for {$item->name}."]);
                    }
                }

                $this->createValuationLayer($line, $item);
            }

            $movement->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $movement->refresh()->load(['lines.item', 'lines.warehouse']);
        });
    }

    public function stockLedger(int $companyId, ?int $itemId = null)
    {
        return ErpStockMovementLine::query()
            ->with(['movement', 'item', 'warehouse'])
            ->whereHas('movement', fn ($query) => $query->where('company_id', $companyId)->where('status', 'posted'))
            ->when($itemId, fn ($query) => $query->where('item_id', $itemId))
            ->latest('id')
            ->get();
    }

    public function valuation(int $companyId)
    {
        return ErpStockValuationLayer::query()
            ->selectRaw('item_id, warehouse_id, SUM(remaining_quantity) as quantity, SUM(value) as value')
            ->with(['item', 'warehouse'])
            ->whereHas('item', fn ($query) => $query->where('company_id', $companyId))
            ->groupBy('item_id', 'warehouse_id')
            ->get();
    }

    private function createValuationLayer(ErpStockMovementLine $line, ErpItem $item): void
    {
        if ($line->quantity_in > 0) {
            ErpStockValuationLayer::create([
                'stock_movement_line_id' => $line->id,
                'item_id' => $line->item_id,
                'warehouse_id' => $line->warehouse_id,
                'quantity_in' => $line->quantity_in,
                'remaining_quantity' => $line->quantity_in,
                'unit_cost' => $line->unit_cost,
                'value' => $line->quantity_in * $line->unit_cost,
            ]);

            return;
        }

        if ($item->costing_method === 'fifo') {
            $this->consumeFifo($line);
            return;
        }

        $averageCost = $this->weightedAverageCost($line->item_id, $line->warehouse_id);
        ErpStockValuationLayer::create([
            'stock_movement_line_id' => $line->id,
            'item_id' => $line->item_id,
            'warehouse_id' => $line->warehouse_id,
            'quantity_out' => $line->quantity_out,
            'unit_cost' => $averageCost,
            'value' => -1 * $line->quantity_out * $averageCost,
        ]);
    }

    private function consumeFifo(ErpStockMovementLine $line): void
    {
        $remaining = $line->quantity_out;
        $layers = ErpStockValuationLayer::where('item_id', $line->item_id)
            ->where('warehouse_id', $line->warehouse_id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, $layer->remaining_quantity);
            $layer->decrement('remaining_quantity', $consume);
            $layer->decrement('value', $consume * $layer->unit_cost);
            $remaining -= $consume;
        }

        ErpStockValuationLayer::create([
            'stock_movement_line_id' => $line->id,
            'item_id' => $line->item_id,
            'warehouse_id' => $line->warehouse_id,
            'quantity_out' => $line->quantity_out,
            'unit_cost' => $line->unit_cost,
            'value' => -1 * $line->quantity_out * $line->unit_cost,
        ]);
    }

    private function availableQuantity(int $itemId, ?int $warehouseId): float
    {
        return (float) ErpStockValuationLayer::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->sum('remaining_quantity');
    }

    private function weightedAverageCost(int $itemId, ?int $warehouseId): float
    {
        $quantity = $this->availableQuantity($itemId, $warehouseId);
        if ($quantity <= 0) {
            return 0;
        }

        $value = (float) ErpStockValuationLayer::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->sum('value');

        return $value / $quantity;
    }

    private function nextMovementNumber(int $companyId, string $movementType): string
    {
        $prefix = strtoupper(substr($movementType, 0, 3));
        $next = ErpStockMovement::where('company_id', $companyId)->where('movement_type', $movementType)->count() + 1;

        return $prefix . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
