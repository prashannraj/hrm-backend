<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal Vouchers - additional indexes (index on company_id, fiscal_year_id, status already exists)
        Schema::table('erp_journal_vouchers', function (Blueprint $table) {
            // Index on voucher_type and voucher_number for numbering
            $table->index(['company_id', 'voucher_type', 'voucher_number'], 'erp_vouchers_type_number_idx');
        });

        // Journal Lines - additional indexes
        Schema::table('erp_journal_lines', function (Blueprint $table) {
            $table->index(['journal_voucher_id', 'account_id'], 'erp_lines_voucher_account_idx');
        });

        // Account - additional indexes
        Schema::table('erp_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'code'], 'erp_accounts_code_idx');
        });

        // Opening Balance - additional indexes
        Schema::table('erp_account_opening_balances', function (Blueprint $table) {
            $table->index(['company_id', 'fiscal_year_id', 'account_id'], 'erp_opening_comp_fy_acc_idx');
        });

        // Commercial Documents - additional indexes
        Schema::table('erp_commercial_documents', function (Blueprint $table) {
            $table->index(['company_id', 'document_type', 'document_date_ad'], 'erp_docs_type_date_idx');
            $table->index(['party_id', 'document_date_ad'], 'erp_docs_party_date_idx');
        });

        // Commercial Document Lines - additional indexes
        Schema::table('erp_commercial_document_lines', function (Blueprint $table) {
            $table->index(['commercial_document_id', 'item_id'], 'erp_doc_lines_doc_item_idx');
            $table->index(['commercial_document_id', 'account_id'], 'erp_doc_lines_doc_acc_idx');
        });

        // Stock Movement - additional indexes
        Schema::table('erp_stock_movements', function (Blueprint $table) {
            $table->index(['company_id', 'movement_date_ad'], 'erp_stock_mov_date_idx');
        });

        // Stock Movement Lines - additional indexes
        Schema::table('erp_stock_movement_lines', function (Blueprint $table) {
            $table->index(['stock_movement_id', 'item_id'], 'erp_stock_lines_mov_item_idx');
            $table->index(['item_id', 'warehouse_id'], 'erp_stock_lines_item_wh_idx');
        });

        // Stock Valuation Layer - additional indexes
        Schema::table('erp_stock_valuation_layers', function (Blueprint $table) {
            $table->index(['company_id', 'item_id', 'warehouse_id'], 'erp_valuation_comp_item_wh_idx');
            $table->index(['item_id', 'remaining_quantity'], 'erp_valuation_item_qty_idx');
        });

        // Parties - additional indexes
        Schema::table('erp_parties', function (Blueprint $table) {
            $table->index(['company_id', 'party_type', 'is_active'], 'erp_parties_type_active_idx');
            $table->index(['company_id', 'pan'], 'erp_parties_pan_idx');
        });

        // Bank Accounts - additional indexes
        Schema::table('erp_bank_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'is_active'], 'erp_bank_accounts_active_idx');
        });

        // Bank Statements - additional indexes
        Schema::table('erp_bank_statements', function (Blueprint $table) {
            $table->index(['company_id', 'bank_account_id', 'status'], 'erp_bank_stmts_acc_status_idx');
        });

        // Audit Events - additional indexes
        Schema::table('erp_audit_events', function (Blueprint $table) {
            $table->index(['company_id', 'event_type', 'created_at'], 'erp_audit_events_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('erp_journal_vouchers', function (Blueprint $table) {
            $table->dropIndex('erp_vouchers_type_number_idx');
        });

        Schema::table('erp_journal_lines', function (Blueprint $table) {
            $table->dropIndex('erp_lines_voucher_account_idx');
        });

        Schema::table('erp_accounts', function (Blueprint $table) {
            $table->dropIndex('erp_accounts_code_idx');
        });

        Schema::table('erp_account_opening_balances', function (Blueprint $table) {
            $table->dropIndex('erp_opening_comp_fy_acc_idx');
        });

        Schema::table('erp_commercial_documents', function (Blueprint $table) {
            $table->dropIndex('erp_docs_type_date_idx');
            $table->dropIndex('erp_docs_party_date_idx');
        });

        Schema::table('erp_commercial_document_lines', function (Blueprint $table) {
            $table->dropIndex('erp_doc_lines_doc_item_idx');
            $table->dropIndex('erp_doc_lines_doc_acc_idx');
        });

        Schema::table('erp_stock_movements', function (Blueprint $table) {
            $table->dropIndex('erp_stock_mov_date_idx');
        });

        Schema::table('erp_stock_movement_lines', function (Blueprint $table) {
            $table->dropIndex('erp_stock_lines_mov_item_idx');
            $table->dropIndex('erp_stock_lines_item_wh_idx');
        });

        Schema::table('erp_stock_valuation_layers', function (Blueprint $table) {
            $table->dropIndex('erp_valuation_comp_item_wh_idx');
            $table->dropIndex('erp_valuation_item_qty_idx');
        });

        Schema::table('erp_parties', function (Blueprint $table) {
            $table->dropIndex('erp_parties_type_active_idx');
            $table->dropIndex('erp_parties_pan_idx');
        });

        Schema::table('erp_bank_accounts', function (Blueprint $table) {
            $table->dropIndex('erp_bank_accounts_active_idx');
        });

        Schema::table('erp_bank_statements', function (Blueprint $table) {
            $table->dropIndex('erp_bank_stmts_acc_status_idx');
        });

        Schema::table('erp_audit_events', function (Blueprint $table) {
            $table->dropIndex('erp_audit_events_type_created_idx');
        });
    }
};