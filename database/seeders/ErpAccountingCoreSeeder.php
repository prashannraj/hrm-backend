<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ErpAccountingCoreSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $companyId = DB::table('erp_companies')->updateOrInsert(
            ['pan' => '302910392'],
            [
                'name' => 'Glow Forward Foundation',
                'legal_name' => 'Glow Forward Foundation Nepal',
                'vat_number' => null,
                'registration_number' => 'Reg-39201/SWC-9201',
                'email' => 'info@glowforward.org',
                'phone' => '+977-1-5523019',
                'address' => 'Sanepa Heights, Lalitpur, Nepal',
                'base_currency_code' => 'NPR',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $company = DB::table('erp_companies')->where('pan', '302910392')->first();

        DB::table('erp_currencies')->updateOrInsert(
            ['code' => 'NPR'],
            [
                'name' => 'Nepalese Rupee',
                'symbol' => 'Rs.',
                'decimal_places' => 2,
                'is_base' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('erp_currencies')->updateOrInsert(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_base' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('erp_branches')->updateOrInsert(
            ['company_id' => $company->id, 'code' => 'HO'],
            [
                'name' => 'Head Office',
                'pan' => '302910392',
                'vat_number' => null,
                'address' => 'Sanepa Heights, Lalitpur, Nepal',
                'is_head_office' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('erp_fiscal_years')->updateOrInsert(
            ['company_id' => $company->id, 'name' => '2083/84'],
            [
                'starts_on_ad' => '2026-04-14',
                'ends_on_ad' => '2027-04-13',
                'starts_on_bs' => '2083-01-01',
                'ends_on_bs' => '2083-12-30',
                'is_current' => true,
                'is_closed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $groups = [
            ['1000', 'Assets', 'asset', 'debit', null, 10],
            ['1100', 'Current Assets', 'asset', 'debit', '1000', 11],
            ['1200', 'Non Current Assets', 'asset', 'debit', '1000', 12],
            ['2000', 'Liabilities', 'liability', 'credit', null, 20],
            ['2100', 'Current Liabilities', 'liability', 'credit', '2000', 21],
            ['3000', 'Equity and Fund Balance', 'equity', 'credit', null, 30],
            ['4000', 'Income', 'income', 'credit', null, 40],
            ['5000', 'Expenses', 'expense', 'debit', null, 50],
        ];

        foreach ($groups as [$code, $name, $type, $normalBalance, $parentCode, $sortOrder]) {
            $parentId = $parentCode ? DB::table('erp_account_groups')->where('company_id', $company->id)->where('code', $parentCode)->value('id') : null;

            DB::table('erp_account_groups')->updateOrInsert(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'parent_id' => $parentId,
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'sort_order' => $sortOrder,
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $accounts = [
            ['1101', 'Cash in Hand', '1100', 'asset', 'debit', true, false, false],
            ['1102', 'Bank Account', '1100', 'asset', 'debit', false, true, false],
            ['1103', 'Accounts Receivable', '1100', 'asset', 'debit', false, false, false],
            ['1104', 'VAT Receivable', '1100', 'asset', 'debit', false, false, true],
            ['1201', 'Fixed Assets', '1200', 'asset', 'debit', false, false, false],
            ['2101', 'Accounts Payable', '2100', 'liability', 'credit', false, false, false],
            ['2102', 'VAT Payable', '2100', 'liability', 'credit', false, false, true],
            ['2103', 'TDS Payable', '2100', 'liability', 'credit', false, false, true],
            ['2104', 'Salary Payable', '2100', 'liability', 'credit', false, false, false],
            ['3001', 'Opening Fund Balance', '3000', 'equity', 'credit', false, false, false],
            ['4001', 'Service Income', '4000', 'income', 'credit', false, false, false],
            ['4002', 'Grant Income', '4000', 'income', 'credit', false, false, false],
            ['5001', 'Salary Expense', '5000', 'expense', 'debit', false, false, false],
            ['5002', 'Office Expense', '5000', 'expense', 'debit', false, false, false],
            ['5003', 'Travel Expense', '5000', 'expense', 'debit', false, false, false],
        ];

        foreach ($accounts as [$code, $name, $groupCode, $type, $normalBalance, $isCash, $isBank, $isTax]) {
            $groupId = DB::table('erp_account_groups')->where('company_id', $company->id)->where('code', $groupCode)->value('id');

            DB::table('erp_accounts')->updateOrInsert(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'account_group_id' => $groupId,
                    'parent_id' => null,
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'is_control_account' => in_array($code, ['1103', '2101'], true),
                    'is_cash_account' => $isCash,
                    'is_bank_account' => $isBank,
                    'is_tax_account' => $isTax,
                    'currency_code' => 'NPR',
                    'allow_manual_posting' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $branchId = DB::table('erp_branches')->where('company_id', $company->id)->where('code', 'HO')->value('id');
        $fiscalYearId = DB::table('erp_fiscal_years')->where('company_id', $company->id)->where('name', '2083/84')->value('id');

        DB::table('erp_accounting_settings')->updateOrInsert(
            ['company_id' => $company->id],
            [
                'default_branch_id' => $branchId,
                'current_fiscal_year_id' => $fiscalYearId,
                'base_currency_code' => 'NPR',
                'date_display_mode' => 'ad_bs',
                'require_voucher_approval' => false,
                'allow_unpost' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
