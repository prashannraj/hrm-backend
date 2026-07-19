<?php

namespace Tests\Feature;

use App\Models\ErpItem;
use App\Models\ErpItemCategory;
use App\Models\ErpUnit;
use App\Models\ErpWarehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_masters_can_be_created(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $category = $this->postJson('/api/v1/inventory/categories', [
            'code' => 'RAW',
            'name' => 'Raw Materials',
        ]);
        $category->assertStatus(201);

        $unit = $this->postJson('/api/v1/inventory/units', [
            'code' => 'PCS',
            'name' => 'Pieces',
            'decimal_places' => 0,
        ]);
        $unit->assertStatus(201);

        $warehouse = $this->postJson('/api/v1/inventory/warehouses', [
            'code' => 'MAIN',
            'name' => 'Main Godown',
            'is_default' => true,
        ]);
        $warehouse->assertStatus(201);

        $item = $this->postJson('/api/v1/inventory/items', [
            'item_category_id' => $category->json('id'),
            'unit_id' => $unit->json('id'),
            'default_warehouse_id' => $warehouse->json('id'),
            'sku' => 'ITEM-001',
            'name' => 'Test Item',
            'costing_method' => 'weighted_average',
            'standard_rate' => 100,
            'reorder_level' => 5,
        ]);
        $item->assertStatus(201);

        $this->assertDatabaseHas('erp_items', ['sku' => 'ITEM-001', 'name' => 'Test Item']);
    }

    public function test_opening_stock_posts_valuation_layer(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$item, $warehouse] = $this->inventoryFixture('weighted_average');

        $response = $this->postJson('/api/v1/inventory/stock-movements', [
            'movement_type' => 'opening_stock',
            'movement_date_ad' => now()->toDateString(),
            'post_now' => true,
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity_in' => 10,
                'unit_cost' => 50,
            ]],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('erp_stock_valuation_layers', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'remaining_quantity' => 10,
            'value' => 500,
        ]);
    }

    public function test_negative_stock_is_restricted(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$item, $warehouse] = $this->inventoryFixture('fifo');

        $response = $this->postJson('/api/v1/inventory/stock-movements', [
            'movement_type' => 'adjustment_out',
            'movement_date_ad' => now()->toDateString(),
            'post_now' => true,
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity_out' => 5,
                'unit_cost' => 50,
            ]],
        ]);

        $response->assertStatus(422);
    }

    private function inventoryFixture(string $costingMethod): array
    {
        $category = ErpItemCategory::create([
            'company_id' => 1,
            'code' => 'INV',
            'name' => 'Inventory',
        ]);
        $unit = ErpUnit::create([
            'company_id' => 1,
            'code' => 'PCS',
            'name' => 'Pieces',
        ]);
        $warehouse = ErpWarehouse::create([
            'company_id' => 1,
            'branch_id' => 1,
            'code' => 'MAIN',
            'name' => 'Main Godown',
        ]);
        $item = ErpItem::create([
            'company_id' => 1,
            'item_category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'sku' => 'ITEM-' . strtoupper($costingMethod),
            'name' => 'Inventory Item',
            'costing_method' => $costingMethod,
        ]);

        return [$item, $warehouse];
    }
}
