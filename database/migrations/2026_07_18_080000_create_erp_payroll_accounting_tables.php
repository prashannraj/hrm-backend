<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->down();

        Schema::create('erp_payroll_posting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('salary_expense_account_id')->constrained('erp_accounts', 'id', 'erp_payroll_salary_exp_acct_fk')->restrictOnDelete();
            $table->foreignId('salary_payable_account_id')->constrained('erp_accounts', 'id', 'erp_payroll_salary_payable_acct_fk')->restrictOnDelete();
            $table->foreignId('allowance_expense_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_allowance_exp_acct_fk')->nullOnDelete();
            $table->foreignId('deduction_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_deduction_acct_fk')->nullOnDelete();
            $table->foreignId('tds_payable_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_tds_payable_acct_fk')->nullOnDelete();
            $table->foreignId('pf_payable_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_pf_payable_acct_fk')->nullOnDelete();
            $table->foreignId('ssf_payable_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_ssf_payable_acct_fk')->nullOnDelete();
            $table->foreignId('cit_payable_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_cit_payable_acct_fk')->nullOnDelete();
            $table->foreignId('advance_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_advance_acct_fk')->nullOnDelete();
            $table->foreignId('loan_recovery_account_id')->nullable()->constrained('erp_accounts', 'id', 'erp_payroll_loan_recovery_acct_fk')->nullOnDelete();
            $table->json('statutory_rates')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('erp_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->restrictOnDelete();
            $table->foreignId('journal_voucher_id')->nullable()->constrained('erp_journal_vouchers')->nullOnDelete();
            $table->string('payroll_number', 50);
            $table->string('period_month', 7);
            $table->date('period_from');
            $table->date('period_to');
            $table->date('posting_date_ad');
            $table->string('posting_date_bs', 20)->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('gross_salary', 18, 2)->default(0);
            $table->decimal('salary_expense', 18, 2)->default(0);
            $table->decimal('allowances', 18, 2)->default(0);
            $table->decimal('deductions', 18, 2)->default(0);
            $table->decimal('tds', 18, 2)->default(0);
            $table->decimal('pf', 18, 2)->default(0);
            $table->decimal('ssf_employee', 18, 2)->default(0);
            $table->decimal('ssf_employer', 18, 2)->default(0);
            $table->decimal('cit', 18, 2)->default(0);
            $table->decimal('advance_recovery', 18, 2)->default(0);
            $table->decimal('loan_recovery', 18, 2)->default(0);
            $table->decimal('net_payable', 18, 2)->default(0);
            $table->json('attendance_summary')->nullable();
            $table->json('leave_summary')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'period_month'], 'erp_payroll_company_month_unique');
            $table->index(['company_id', 'fiscal_year_id', 'status'], 'erp_payroll_company_fy_status_idx');
        });

        Schema::create('erp_payroll_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('erp_payroll_runs')->cascadeOnDelete();
            $table->string('employee_id');
            $table->string('employee_name');
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('pan')->nullable();
            $table->string('ssf_number')->nullable();
            $table->decimal('basic_salary', 18, 2)->default(0);
            $table->decimal('prorated_basic', 18, 2)->default(0);
            $table->decimal('allowances', 18, 2)->default(0);
            $table->decimal('gross_salary', 18, 2)->default(0);
            $table->decimal('deductions', 18, 2)->default(0);
            $table->decimal('tds', 18, 2)->default(0);
            $table->decimal('pf', 18, 2)->default(0);
            $table->decimal('ssf_employee', 18, 2)->default(0);
            $table->decimal('ssf_employer', 18, 2)->default(0);
            $table->decimal('cit', 18, 2)->default(0);
            $table->decimal('advance_recovery', 18, 2)->default(0);
            $table->decimal('loan_recovery', 18, 2)->default(0);
            $table->decimal('net_payable', 18, 2)->default(0);
            $table->unsignedTinyInteger('present_days')->default(0);
            $table->unsignedTinyInteger('leave_days')->default(0);
            $table->unsignedTinyInteger('unpaid_days')->default(0);
            $table->unsignedTinyInteger('payable_days')->default(0);
            $table->json('component_breakdown')->nullable();
            $table->timestamps();

            $table->index(['payroll_run_id', 'employee_id'], 'erp_payroll_run_emp_idx');
        });

        Schema::create('erp_payroll_posting_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('erp_payroll_runs')->cascadeOnDelete();
            $table->foreignId('original_journal_voucher_id')->constrained('erp_journal_vouchers', 'id', 'erp_payroll_orig_jv_fk')->restrictOnDelete();
            $table->foreignId('reversal_journal_voucher_id')->nullable()->constrained('erp_journal_vouchers', 'id', 'erp_payroll_reversal_jv_fk')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payroll_posting_reversals');
        Schema::dropIfExists('erp_payroll_run_lines');
        Schema::dropIfExists('erp_payroll_runs');
        Schema::dropIfExists('erp_payroll_posting_settings');
    }
};
