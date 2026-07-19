<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->string('party_type', 30);
            $table->string('code', 40);
            $table->string('name');
            $table->string('pan', 50)->nullable();
            $table->string('vat_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('billing_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'party_type', 'is_active']);
        });

        Schema::create('erp_commercial_document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->string('prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'fiscal_year_id', 'document_type'], 'erp_doc_series_unique');
        });

        Schema::create('erp_commercial_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('erp_parties')->nullOnDelete();
            $table->foreignId('journal_voucher_id')->nullable()->constrained('erp_journal_vouchers')->nullOnDelete();
            $table->string('document_type', 40);
            $table->string('document_number', 60);
            $table->date('document_date_ad');
            $table->string('document_date_bs', 20)->nullable();
            $table->string('reference_number')->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('vat_total', 18, 4)->default(0);
            $table->decimal('tds_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'document_number']);
            $table->index(['company_id', 'document_type', 'status']);
        });

        Schema::create('erp_commercial_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_document_id')->constrained('erp_commercial_documents')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('rate', 18, 4)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 18, 4)->default(0);
            $table->decimal('tds_rate', 5, 2)->default(0);
            $table->decimal('tds_amount', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4)->default(0);
            $table->unsignedInteger('line_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_commercial_document_lines');
        Schema::dropIfExists('erp_commercial_documents');
        Schema::dropIfExists('erp_commercial_document_series');
        Schema::dropIfExists('erp_parties');
    }
};
