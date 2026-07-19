<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('erp_voucher_reversals');
        Schema::dropIfExists('erp_voucher_attachments');
        Schema::dropIfExists('erp_voucher_approvals');
        Schema::dropIfExists('erp_voucher_series');

        Schema::create('erp_voucher_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('erp_branches')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
            $table->string('voucher_type', 30);
            $table->string('prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'fiscal_year_id', 'voucher_type'], 'erp_voucher_series_unique');
        });

        Schema::create('erp_voucher_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained('erp_journal_vouchers')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('submitted');
            $table->text('remarks')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['journal_voucher_id', 'status']);
        });

        Schema::create('erp_voucher_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained('erp_journal_vouchers')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['journal_voucher_id']);
        });

        Schema::create('erp_voucher_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_voucher_id')->constrained('erp_journal_vouchers')->cascadeOnDelete();
            $table->foreignId('reversal_voucher_id')->constrained('erp_journal_vouchers')->cascadeOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamp('reversed_at');
            $table->timestamps();

            $table->unique('original_voucher_id');
            $table->unique('reversal_voucher_id');
        });

        Schema::table('erp_journal_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('erp_journal_vouchers', 'party_name')) {
                $table->string('party_name')->nullable()->after('narration');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('party_name');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'reference_date_ad')) {
                $table->date('reference_date_ad')->nullable()->after('reference_number');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'reference_date_bs')) {
                $table->string('reference_date_bs', 20)->nullable()->after('reference_date_ad');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('posted_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }

            if (! Schema::hasColumn('erp_journal_vouchers', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('erp_journal_vouchers')) {
            $foreignKeyNames = [];

            foreach (['approved_by', 'cancelled_by'] as $column) {
                $constraintName = $this->foreignKeyName('erp_journal_vouchers', $column);

                if ($constraintName !== null) {
                    $foreignKeyNames[] = $constraintName;
                }
            }

            foreach ($foreignKeyNames as $constraintName) {
                DB::statement("ALTER TABLE `erp_journal_vouchers` DROP FOREIGN KEY `{$constraintName}`");
            }

            $columns = [
                'party_name',
                'reference_number',
                'reference_date_ad',
                'reference_date_bs',
                'approved_by',
                'approved_at',
                'cancelled_by',
                'cancelled_at',
                'cancellation_reason',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('erp_journal_vouchers', $column)
            ));

            if (! empty($existingColumns)) {
                Schema::table('erp_journal_vouchers', function (Blueprint $table) use ($existingColumns) {
                    $table->dropColumn($existingColumns);
                });
            }
        }

        Schema::dropIfExists('erp_voucher_reversals');
        Schema::dropIfExists('erp_voucher_attachments');
        Schema::dropIfExists('erp_voucher_approvals');
        Schema::dropIfExists('erp_voucher_series');
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
    }
};
