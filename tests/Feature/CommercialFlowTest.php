<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpItem;
use App\Models\ErpItemCategory;
use App\Models\ErpParty;
use App\Models\ErpUnit;
use App\Models\ErpWarehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommercialFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_can_be_created(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        $account = ErpAccount::query()->first();

        $response = $this->postJson('/api/v1/commercial/parties', [
            'account_id' => $account->id,
            'party_type' => 'customer',
            'code' => 'CUS-001',
            'name' => 'Test Customer',
            'pan' => '123456789',
            'phone' => '9800000000',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('erp_parties', ['code' => 'CUS-001', 'party_type' => 'customer']);
    }

    public function test_purchase_bill_can_be_posted_with_stock_and_accounting_entry(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$item, $warehouse, $account] = $this->commercialFixture();
        $vendor = ErpParty::create([
            'company_id' => 1,
            'account_id' => $account->id,
            'party_type' => 'vendor',
            'code' => 'VEN-001',
            'name' => 'Test Vendor',
        ]);

        $response = $this->postJson('/api/v1/commercial/documents', [
            'party_id' => $vendor->id,
            'document_type' => 'purchase_bill',
            'document_date_ad' => now()->toDateString(),
            'post_now' => true,
            'lines' => [[
                'item_id' => $item->id,
                'account_id' => $account->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
                'rate' => 100,
                'discount_rate' => 10,
                'vat_rate' => 13,
            ]],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('erp_commercial_documents', ['document_type' => 'purchase_bill', 'status' => 'posted']);
        $this->assertDatabaseHas('erp_stock_movements', ['movement_type' => 'purchase_receipt', 'status' => 'posted']);
        $this->assertDatabaseHas('erp_journal_vouchers', ['voucher_type' => 'purchase', 'status' => 'posted']);
    }

    public function test_document_totals_calculate_discount_vat_and_tds(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$item, $warehouse, $account] = $this->commercialFixture();

        $response = $this->postJson('/api/v1/commercial/documents', [
            'document_type' => 'purchase_order',
            'document_date_ad' => now()->toDateString(),
            'lines' => [[
                'item_id' => $item->id,
                'account_id' => $account->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 2,
                'rate' => 1000,
                'discount_rate' => 10,
                'vat_rate' => 13,
                'tds_rate' => 1.5,
            ]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('subtotal', 2000);
        $response->assertJsonPath('discount_total', 200);
        $response->assertJsonPath('vat_total', 234);
        $response->assertJsonPath('tds_total', 27);
        $response->assertJsonPath('grand_total', 2007);
    }

    private function commercialFixture(): array
    {
        $account = ErpAccount::query()->first();
        $category = ErpItemCategory::create(['company_id' => 1, 'code' => 'SALE', 'name' => 'Sale Items']);
        $unit = ErpUnit::create(['company_id' => 1, 'code' => 'PCS', 'name' => 'Pieces']);
        $warehouse = ErpWarehouse::create(['company_id' => 1, 'branch_id' => 1, 'code' => 'MAIN', 'name' => 'Main Godown']);
        $item = ErpItem::create([
            'company_id' => 1,
            'item_category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'sku' => 'COM-ITEM',
            'name' => 'Commercial Item',
            'costing_method' => 'weighted_average',
        ]);

        return [$item, $warehouse, $account];
    }
}
