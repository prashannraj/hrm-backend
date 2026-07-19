<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number', 100);
            $table->string('branch_name')->nullable();
            $table->string('ifsc_code', 50)->nullable();
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'erp_bank_acct_company_active_idx');
        });

        Schema::create('erp_bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('erp_bank_accounts')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->date('statement_date');
            $table->string('reference_number')->nullable();
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('closing_balance', 18, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id', 'statement_date'], 'erp_bank_stmt_company_account_date_idx');
        });

        Schema::create('erp_bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('erp_bank_statements')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('reference', 120)->nullable();
            $table->string('description');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('amount', 18, 4)->default(0);
            $table->boolean('is_matched')->default(false);
            $table->foreignId('matched_voucher_line_id')->nullable()->constrained('erp_journal_lines')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('erp_bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('erp_bank_accounts')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->date('reconciled_on');
            $table->decimal('statement_balance', 18, 4)->default(0);
            $table->decimal('book_balance', 18, 4)->default(0);
            $table->decimal('difference', 18, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->json('matched_lines')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('erp_petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('current_balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('erp_petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_fund_id')->constrained('erp_petty_cash_funds')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('txn_type', 30);
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 4)->default(0);
            $table->foreignId('journal_voucher_id')->nullable()->constrained('erp_journal_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_petty_cash_transactions');
        Schema::dropIfExists('erp_petty_cash_funds');
        Schema::dropIfExists('erp_bank_reconciliations');
        Schema::dropIfExists('erp_bank_statement_lines');
        Schema::dropIfExists('erp_bank_statements');
        Schema::dropIfExists('erp_bank_accounts');
    }
};
