<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('tax_type', 20);
            $table->string('code', 40);
            $table->string('name');
            $table->decimal('rate', 7, 4)->default(0);
            $table->string('section', 80)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'tax_type', 'code'], 'erp_tax_rate_company_type_code_unique');
            $table->index(['company_id', 'tax_type', 'is_active'], 'erp_tax_rate_company_type_active_idx');
        });

        Schema::create('erp_billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->string('profile_type', 40);
            $table->string('display_name');
            $table->string('series_prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->string('print_layout', 40)->default('a4');
            $table->boolean('requires_vat')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'fiscal_year_id', 'profile_type'], 'erp_billing_profile_unique');
        });

        Schema::create('erp_tax_invoice_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->nullable()->constrained('erp_commercial_documents')->nullOnDelete();
            $table->string('audit_type', 40);
            $table->string('severity', 20)->default('info');
            $table->string('message');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'audit_type', 'severity'], 'erp_tax_audit_company_type_severity_idx');
        });

        Schema::create('erp_tax_period_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->string('report_type', 30);
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('taxable_sales', 18, 4)->default(0);
            $table->decimal('sales_vat', 18, 4)->default(0);
            $table->decimal('taxable_purchases', 18, 4)->default(0);
            $table->decimal('purchase_vat', 18, 4)->default(0);
            $table->decimal('tds_deducted', 18, 4)->default(0);
            $table->decimal('net_payable', 18, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'report_type', 'period_from', 'period_to'], 'erp_tax_report_company_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_tax_period_reports');
        Schema::dropIfExists('erp_tax_invoice_audits');
        Schema::dropIfExists('erp_billing_profiles');
        Schema::dropIfExists('erp_tax_rates');
    }
};
