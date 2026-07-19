<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tables if they exist to avoid migration failure due to dirty state
        $this->down();

        Schema::create('erp_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('pan')->nullable()->index();
            $table->string('vat_number')->nullable()->index();
            $table->string('registration_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('base_currency_code', 3)->default('NPR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('erp_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('pan')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('erp_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->string('name', 20);
            $table->date('starts_on_ad');
            $table->date('ends_on_ad');
            $table->string('starts_on_bs', 20);
            $table->string('ends_on_bs', 20);
            $table->boolean('is_current')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_current', 'is_closed']);
        });

        Schema::create('erp_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('erp_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('erp_currencies')->restrictOnDelete();
            $table->date('effective_date_ad');
            $table->string('effective_date_bs', 20)->nullable();
            $table->decimal('rate_to_base', 18, 8);
            $table->timestamps();

            $table->unique(['company_id', 'currency_id', 'effective_date_ad'], 'erp_ex_rate_unique');
        });

        Schema::create('erp_account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('erp_account_groups')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type', 'is_active']);
        });

        Schema::create('erp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('account_group_id')->constrained('erp_account_groups')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_tax_account')->default(false);
            $table->string('pan')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('currency_code', 3)->default('NPR');
            $table->boolean('allow_manual_posting')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'account_group_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('erp_accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('default_branch_id')->nullable()->constrained('erp_branches')->nullOnDelete();
            $table->foreignId('current_fiscal_year_id')->nullable()->constrained('erp_fiscal_years')->nullOnDelete();
            $table->string('base_currency_code', 3)->default('NPR');
            $table->string('date_display_mode', 10)->default('ad_bs');
            $table->boolean('require_voucher_approval')->default(false);
            $table->boolean('allow_unpost')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('erp_account_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('erp_branches')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('erp_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'fiscal_year_id', 'account_id'], 'erp_opening_unique');
            $table->index(['company_id', 'fiscal_year_id']);
        });

        Schema::create('erp_journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->restrictOnDelete();
            $table->string('voucher_type', 30)->default('journal');
            $table->string('voucher_number', 50);
            $table->date('voucher_date_ad');
            $table->string('voucher_date_bs', 20)->nullable();
            $table->text('narration')->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'fiscal_year_id', 'voucher_type', 'voucher_number'], 'erp_voucher_number_unique');
            $table->index(['company_id', 'fiscal_year_id', 'status']);
            $table->index(['voucher_date_ad']);
        });

        Schema::create('erp_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained('erp_journal_vouchers')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('erp_accounts')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('erp_branches')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->unsignedInteger('line_order')->default(0);
            $table->timestamps();

            $table->index(['account_id']);
            $table->index(['journal_voucher_id', 'line_order']);
        });

        Schema::create('erp_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('erp_companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('payload')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'event_type']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('erp_audit_events');
        Schema::dropIfExists('erp_journal_lines');
        Schema::dropIfExists('erp_journal_vouchers');
        Schema::dropIfExists('erp_account_opening_balances');
        Schema::dropIfExists('erp_accounting_settings');
        Schema::dropIfExists('erp_accounts');
        Schema::dropIfExists('erp_account_groups');
        Schema::dropIfExists('erp_exchange_rates');
        Schema::dropIfExists('erp_currencies');
        Schema::dropIfExists('erp_fiscal_years');
        Schema::dropIfExists('erp_branches');
        Schema::dropIfExists('erp_companies');

        Schema::enableForeignKeyConstraints();
    }
};
