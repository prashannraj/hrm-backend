<?php

namespace Database\Seeders;

use App\Models\ErpCompany;
use App\Models\ErpPermission;
use App\Models\ErpRole;
use Illuminate\Database\Seeder;

class ErpPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define all ERP permissions
        $permissions = [
            // Accounting Core
            ['name' => 'accounting.view', 'display_name' => 'View Accounting', 'module' => 'accounting', 'action' => 'view'],
            ['name' => 'accounting.accounts.create', 'display_name' => 'Create Accounts', 'module' => 'accounting', 'action' => 'create'],
            ['name' => 'accounting.accounts.edit', 'display_name' => 'Edit Accounts', 'module' => 'accounting', 'action' => 'edit'],
            ['name' => 'accounting.opening-balances', 'display_name' => 'Manage Opening Balances', 'module' => 'accounting', 'action' => 'manage'],
            ['name' => 'accounting.journal.create', 'display_name' => 'Create Journal Vouchers', 'module' => 'accounting', 'action' => 'create'],
            ['name' => 'accounting.journal.post', 'display_name' => 'Post Journal Vouchers', 'module' => 'accounting', 'action' => 'post'],
            ['name' => 'accounting.reports', 'display_name' => 'View Reports', 'module' => 'accounting', 'action' => 'reports'],

            // Voucher Operations
            ['name' => 'vouchers.view', 'display_name' => 'View Vouchers', 'module' => 'vouchers', 'action' => 'view'],
            ['name' => 'vouchers.submit', 'display_name' => 'Submit Vouchers', 'module' => 'vouchers', 'action' => 'submit'],
            ['name' => 'vouchers.approve', 'display_name' => 'Approve Vouchers', 'module' => 'vouchers', 'action' => 'approve'],
            ['name' => 'vouchers.cancel', 'display_name' => 'Cancel Vouchers', 'module' => 'vouchers', 'action' => 'cancel'],
            ['name' => 'vouchers.reverse', 'display_name' => 'Reverse Vouchers', 'module' => 'vouchers', 'action' => 'reverse'],

            // Inventory
            ['name' => 'inventory.view', 'display_name' => 'View Inventory', 'module' => 'inventory', 'action' => 'view'],
            ['name' => 'inventory.masters', 'display_name' => 'Manage Inventory Masters', 'module' => 'inventory', 'action' => 'manage'],
            ['name' => 'inventory.movements', 'display_name' => 'Manage Stock Movements', 'module' => 'inventory', 'action' => 'manage'],
            ['name' => 'inventory.reports', 'display_name' => 'View Inventory Reports', 'module' => 'inventory', 'action' => 'reports'],

            // Commercial (Procurement & Sales)
            ['name' => 'commercial.view', 'display_name' => 'View Commercial', 'module' => 'commercial', 'action' => 'view'],
            ['name' => 'commercial.parties', 'display_name' => 'Manage Parties', 'module' => 'commercial', 'action' => 'manage'],
            ['name' => 'commercial.documents', 'display_name' => 'Manage Documents', 'module' => 'commercial', 'action' => 'manage'],
            ['name' => 'commercial.post', 'display_name' => 'Post Commercial Documents', 'module' => 'commercial', 'action' => 'post'],

            // Billing & Tax
            ['name' => 'billing.view', 'display_name' => 'View Billing', 'module' => 'billing', 'action' => 'view'],
            ['name' => 'billing.settings', 'display_name' => 'Manage Billing Settings', 'module' => 'billing', 'action' => 'manage'],
            ['name' => 'billing.reports', 'display_name' => 'View Billing Reports', 'module' => 'billing', 'action' => 'reports'],

            // Banking
            ['name' => 'banking.view', 'display_name' => 'View Banking', 'module' => 'banking', 'action' => 'view'],
            ['name' => 'banking.accounts', 'display_name' => 'Manage Bank Accounts', 'module' => 'banking', 'action' => 'manage'],
            ['name' => 'banking.reconciliation', 'display_name' => 'Bank Reconciliation', 'module' => 'banking', 'action' => 'reconcile'],
            ['name' => 'banking.petty-cash', 'display_name' => 'Manage Petty Cash', 'module' => 'banking', 'action' => 'manage'],

            // Advanced Finance
            ['name' => 'advanced-finance.view', 'display_name' => 'View Advanced Finance', 'module' => 'advanced-finance', 'action' => 'view'],
            ['name' => 'advanced-finance.assets', 'display_name' => 'Manage Fixed Assets', 'module' => 'advanced-finance', 'action' => 'manage'],
            ['name' => 'advanced-finance.loans', 'display_name' => 'Manage Loans', 'module' => 'advanced-finance', 'action' => 'manage'],
            ['name' => 'advanced-finance.budgets', 'display_name' => 'Manage Budgets', 'module' => 'advanced-finance', 'action' => 'manage'],

            // Payroll Accounting
            ['name' => 'payroll-accounting.view', 'display_name' => 'View Payroll Accounting', 'module' => 'payroll-accounting', 'action' => 'view'],
            ['name' => 'payroll-accounting.settings', 'display_name' => 'Manage Payroll Settings', 'module' => 'payroll-accounting', 'action' => 'manage'],
            ['name' => 'payroll-accounting.post', 'display_name' => 'Post Payroll', 'module' => 'payroll-accounting', 'action' => 'post'],

            // Reports
            ['name' => 'reports.view', 'display_name' => 'View All Reports', 'module' => 'reports', 'action' => 'view'],
            ['name' => 'reports.export', 'display_name' => 'Export Reports', 'module' => 'reports', 'action' => 'export'],

            // Admin
            ['name' => 'admin.users', 'display_name' => 'Manage Users', 'module' => 'admin', 'action' => 'manage'],
            ['name' => 'admin.roles', 'display_name' => 'Manage Roles', 'module' => 'admin', 'action' => 'manage'],
            ['name' => 'admin.fiscal-year.close', 'display_name' => 'Close Fiscal Year', 'module' => 'admin', 'action' => 'close'],
            ['name' => 'admin.fiscal-year.reopen', 'display_name' => 'Reopen Fiscal Year', 'module' => 'admin', 'action' => 'reopen'],
            ['name' => 'admin.integrity', 'display_name' => 'Run Integrity Checks', 'module' => 'admin', 'action' => 'manage'],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            ErpPermission::create(array_merge($permission, ['is_system' => true]));
        }

        // Create default roles for each company
        $companies = ErpCompany::all();
        foreach ($companies as $company) {
            // Admin role - full access
            $adminRole = ErpRole::create([
                'company_id' => $company->id,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all ERP modules',
                'is_system' => true,
            ]);
            $adminRole->permissions()->attach(ErpPermission::pluck('id'));

            // Accountant role - accounting and reports
            $accountantRole = ErpRole::create([
                'company_id' => $company->id,
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'Can manage accounting entries and view reports',
                'is_system' => true,
            ]);
            $accountantRole->permissions()->attach(
                ErpPermission::whereIn('name', [
                    'accounting.view', 'accounting.accounts.create', 'accounting.accounts.edit',
                    'accounting.opening-balances', 'accounting.journal.create', 'accounting.journal.post',
                    'accounting.reports', 'vouchers.view', 'vouchers.submit', 'vouchers.approve',
                    'vouchers.cancel', 'vouchers.reverse', 'reports.view', 'reports.export',
                ])->pluck('id')
            );

            // Manager role - view and approve
            $managerRole = ErpRole::create([
                'company_id' => $company->id,
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Can view and approve transactions',
                'is_system' => true,
            ]);
            $managerRole->permissions()->attach(
                ErpPermission::whereIn('name', [
                    'accounting.view', 'accounting.reports', 'vouchers.view', 'vouchers.approve',
                    'inventory.view', 'commercial.view', 'billing.view', 'banking.view',
                    'advanced-finance.view', 'payroll-accounting.view', 'reports.view',
                ])->pluck('id')
            );

            // Viewer role - read only
            $viewerRole = ErpRole::create([
                'company_id' => $company->id,
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Read-only access to reports',
                'is_system' => true,
            ]);
            $viewerRole->permissions()->attach(
                ErpPermission::whereIn('name', [
                    'accounting.view', 'accounting.reports', 'vouchers.view',
                    'inventory.view', 'inventory.reports', 'commercial.view',
                    'billing.view', 'banking.view', 'advanced-finance.view',
                    'payroll-accounting.view', 'reports.view',
                ])->pluck('id')
            );
        }
    }
}