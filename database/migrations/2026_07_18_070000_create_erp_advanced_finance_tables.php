<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_fa_cat_company_code_unique');
        });

        Schema::create('erp_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('erp_cost_centers')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('contract_value', 18, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_cost_center_company_code_unique');
        });

        Schema::create('erp_fixed_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->foreignId('asset_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_fa_cat_asset_acct_fk')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_fa_cat_depr_exp_acct_fk')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_fa_cat_accum_depr_acct_fk')->nullOnDelete();
            $table->string('default_method', 30)->default('straight_line');
            $table->decimal('default_rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'erp_project_company_code_unique');
        });

        Schema::create('erp_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('asset_category_id')->nullable()->constrained('erp_fixed_asset_categories')->nullOnDelete();
            $table->string('source_asset_id')->nullable();
            $table->string('asset_code', 80);
            $table->string('name');
            $table->date('purchase_date');
            $table->date('capitalized_on')->nullable();
            $table->decimal('purchase_cost', 18, 4)->default(0);
            $table->decimal('salvage_value', 18, 4)->default(0);
            $table->integer('useful_life_months')->default(60);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->decimal('depreciation_rate', 8, 4)->default(0);
            $table->decimal('accumulated_depreciation', 18, 4)->default(0);
            $table->decimal('book_value', 18, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->foreignId('project_id')->nullable()->constrained('erp_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('erp_cost_centers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'asset_code'], 'erp_fixed_asset_company_code_unique');
        });

        Schema::create('erp_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 30)->default('draft');
            $table->decimal('total_depreciation', 18, 4)->default(0);
            $table->foreignId('journal_voucher_id')->nullable()->constrained('erp_journal_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('erp_depreciation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depreciation_run_id')->constrained('erp_depreciation_runs')->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained('erp_fixed_assets')->cascadeOnDelete();
            $table->decimal('opening_book_value', 18, 4)->default(0);
            $table->decimal('depreciation_amount', 18, 4)->default(0);
            $table->decimal('closing_book_value', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('erp_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('loan_account_id')->constrained('erp_accounts')->cascadeOnDelete();
            $table->foreignId('interest_account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->string('loan_number', 80);
            $table->string('lender_name');
            $table->date('start_date');
            $table->decimal('principal_amount', 18, 4)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->integer('tenure_months')->default(12);
            $table->decimal('outstanding_principal', 18, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'loan_number'], 'erp_loan_company_number_unique');
        });

        Schema::create('erp_loan_repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('erp_loans')->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('principal_due', 18, 4)->default(0);
            $table->decimal('interest_due', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });

        Schema::create('erp_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 30)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('erp_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('erp_budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('erp_accounts')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('erp_cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('erp_projects')->nullOnDelete();
            $table->decimal('amount', 18, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_budget_lines');
        Schema::dropIfExists('erp_budgets');
        Schema::dropIfExists('erp_loan_repayment_schedules');
        Schema::dropIfExists('erp_loans');
        Schema::dropIfExists('erp_depreciation_lines');
        Schema::dropIfExists('erp_depreciation_runs');
        Schema::dropIfExists('erp_fixed_assets');
        Schema::dropIfExists('erp_fixed_asset_categories');
        Schema::dropIfExists('erp_projects');
        Schema::dropIfExists('erp_cost_centers');
    }
};
