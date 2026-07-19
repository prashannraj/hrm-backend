<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('erp_item_categories')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_item_cat_company_code_unique');
            $table->index(['company_id', 'is_active'], 'erp_item_cat_company_active_idx');
        });

        Schema::create('erp_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_unit_company_code_unique');
        });

        Schema::create('erp_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('erp_branches')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_wh_company_code_unique');
            $table->index(['company_id', 'branch_id', 'is_active'], 'erp_wh_company_branch_active_idx');
        });

        Schema::create('erp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('item_category_id')->constrained('erp_item_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('erp_units')->restrictOnDelete();
            $table->foreignId('default_warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->string('sku', 60);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->enum('costing_method', ['fifo', 'weighted_average'])->default('weighted_average');
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->decimal('minimum_stock', 18, 4)->default(0);
            $table->decimal('maximum_stock', 18, 4)->default(0);
            $table->decimal('reorder_level', 18, 4)->default(0);
            $table->decimal('standard_rate', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'sku'], 'erp_item_company_sku_unique');
            $table->index(['company_id', 'item_category_id', 'is_active'], 'erp_item_company_cat_active_idx');
            $table->index(['barcode'], 'erp_item_barcode_idx');
        });

        Schema::create('erp_item_alternate_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('erp_units')->restrictOnDelete();
            $table->decimal('conversion_factor', 18, 6);
            $table->timestamps();

            $table->unique(['item_id', 'unit_id'], 'erp_item_alt_item_unit_unique');
        });

        Schema::create('erp_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('manufactured_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'batch_number'], 'erp_batch_item_batch_unique');
        });

        Schema::create('erp_item_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->string('serial_number');
            $table->string('status', 30)->default('available');
            $table->timestamps();

            $table->unique(['item_id', 'serial_number'], 'erp_serial_item_number_unique');
            $table->index(['warehouse_id', 'status'], 'erp_serial_wh_status_idx');
        });

        Schema::create('erp_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('erp_branches')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('erp_fiscal_years')->nullOnDelete();
            $table->string('movement_type', 40);
            $table->string('movement_number', 60);
            $table->date('movement_date_ad');
            $table->string('movement_date_bs', 20)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'movement_number'], 'erp_stock_mov_company_number_unique');
            $table->index(['company_id', 'movement_type', 'status'], 'erp_stock_mov_company_type_status_idx');
            $table->index(['reference_type', 'reference_id'], 'erp_stock_ref_type_id_idx');
        });

        Schema::create('erp_stock_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained('erp_stock_movements')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('erp_items')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('erp_item_batches')->nullOnDelete();
            $table->decimal('quantity_in', 18, 4)->default(0);
            $table->decimal('quantity_out', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'warehouse_id'], 'erp_stock_line_item_wh_idx');
        });

        Schema::create('erp_stock_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_line_id')->constrained('erp_stock_movement_lines')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->decimal('quantity_in', 18, 4)->default(0);
            $table->decimal('quantity_out', 18, 4)->default(0);
            $table->decimal('remaining_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('value', 18, 4)->default(0);
            $table->timestamps();

            $table->index(['item_id', 'warehouse_id', 'remaining_quantity'], 'erp_stock_val_item_wh_rem_qty_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_stock_valuation_layers');
        Schema::dropIfExists('erp_stock_movement_lines');
        Schema::dropIfExists('erp_stock_movements');
        Schema::dropIfExists('erp_item_serial_numbers');
        Schema::dropIfExists('erp_item_batches');
        Schema::dropIfExists('erp_item_alternate_units');
        Schema::dropIfExists('erp_items');
        Schema::dropIfExists('erp_warehouses');
        Schema::dropIfExists('erp_units');
        Schema::dropIfExists('erp_item_categories');
    }
};
