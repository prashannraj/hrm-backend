<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AdvancedFinanceController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\BillingTaxController;
use App\Http\Controllers\CommercialController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\PayrollAccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\WfhController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\CorsMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    
    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String()
        ]);
    });

    // Public Auth Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes (using Sanctum token middleware)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth Profiles & Agreements
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/sign-agreement', [AuthController::class, 'signAgreement']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // MIS Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // HRIS (Employees)
        Route::apiResource('employees', EmployeeController::class);

        // Policies
        Route::get('/policies', [PolicyController::class, 'index']);
        Route::post('/policies', [PolicyController::class, 'store']);
        Route::post('/policies/{id}/acknowledge', [PolicyController::class, 'acknowledge']);

        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

        // Leave Requests
        Route::get('/leaves', [LeaveController::class, 'index']);
        Route::post('/leaves', [LeaveController::class, 'store']);
        Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve']);

        // WFH Requests
        Route::get('/wfh', [WfhController::class, 'index']);
        Route::post('/wfh', [WfhController::class, 'store']);
        Route::post('/wfh/{id}/approve', [WfhController::class, 'approve']);

        // Timesheets
        Route::get('/timesheets', [TimesheetController::class, 'index']);
        Route::post('/timesheets', [TimesheetController::class, 'store']);

        // Travel Management
        Route::get('/travel', [TravelController::class, 'index']);
        Route::post('/travel', [TravelController::class, 'store']);
        Route::post('/travel/{id}/approve', [TravelController::class, 'approve']);
        Route::post('/travel/{id}/settle', [TravelController::class, 'settle']);

        // Assets Management
        Route::get('/assets', [AssetController::class, 'index']);
        Route::post('/assets', [AssetController::class, 'store']);
        Route::post('/assets/{id}/maintenance', [AssetController::class, 'logMaintenance']);
        Route::post('/assets/{id}/resolve-maintenance', [AssetController::class, 'resolveMaintenance']);

        // Fleet / Vehicles
        Route::get('/fleet', [FleetController::class, 'index']);
        Route::post('/fleet/logs', [FleetController::class, 'storeLog']);

        // Organization Settings & Logs
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings', [SettingsController::class, 'update']);
        Route::get('/logs', [SettingsController::class, 'auditLogs']);

        // Accounting Core
        Route::prefix('accounting')->group(function () {
            Route::get('/dashboard', [AccountingController::class, 'dashboard']);
            Route::get('/chart-of-accounts', [AccountingController::class, 'chartOfAccounts']);
            Route::post('/accounts', [AccountingController::class, 'storeAccount']);
            Route::get('/opening-balances', [AccountingController::class, 'openingBalances']);
            Route::post('/opening-balances', [AccountingController::class, 'storeOpeningBalance']);
            Route::get('/journal-vouchers', [AccountingController::class, 'journals']);
            Route::post('/journal-vouchers', [AccountingController::class, 'storeJournal']);
            Route::post('/journal-vouchers/{voucher}/post', [AccountingController::class, 'postJournal']);
            Route::get('/ledger', [AccountingController::class, 'ledger']);
            Route::get('/trial-balance', [AccountingController::class, 'trialBalance']);
        });

        // Voucher Operations
        Route::prefix('vouchers')->group(function () {
            Route::get('/series', [VoucherController::class, 'series']);
            Route::post('/{voucher}/submit', [VoucherController::class, 'submit']);
            Route::post('/{voucher}/approve', [VoucherController::class, 'approve']);
            Route::post('/{voucher}/cancel', [VoucherController::class, 'cancel']);
            Route::post('/{voucher}/attachments', [VoucherController::class, 'attach']);
            Route::post('/{voucher}/reverse', [VoucherController::class, 'reverse']);
        });

        // Inventory Foundation
        Route::prefix('inventory')->group(function () {
            Route::get('/dashboard', [InventoryController::class, 'dashboard']);
            Route::get('/masters', [InventoryController::class, 'masters']);
            Route::post('/categories', [InventoryController::class, 'storeCategory']);
            Route::post('/units', [InventoryController::class, 'storeUnit']);
            Route::post('/warehouses', [InventoryController::class, 'storeWarehouse']);
            Route::post('/items', [InventoryController::class, 'storeItem']);
            Route::post('/stock-movements', [InventoryController::class, 'storeMovement']);
            Route::post('/stock-movements/{movement}/post', [InventoryController::class, 'postMovement']);
            Route::get('/stock-ledger', [InventoryController::class, 'ledger']);
            Route::get('/valuation', [InventoryController::class, 'valuation']);
        });

        // Procurement & Sales Flow
        Route::prefix('commercial')->group(function () {
            Route::get('/dashboard', [CommercialController::class, 'dashboard']);
            Route::get('/parties', [CommercialController::class, 'parties']);
            Route::post('/parties', [CommercialController::class, 'storeParty']);
            Route::get('/documents', [CommercialController::class, 'documents']);
            Route::post('/documents', [CommercialController::class, 'storeDocument']);
            Route::post('/documents/{document}/post', [CommercialController::class, 'postDocument']);
            Route::get('/outstanding', [CommercialController::class, 'outstanding']);
        });

        // Nepal Billing & Tax Compliance
        Route::prefix('billing-tax')->group(function () {
            Route::get('/dashboard', [BillingTaxController::class, 'dashboard']);
            Route::get('/rates', [BillingTaxController::class, 'rates']);
            Route::post('/rates', [BillingTaxController::class, 'storeRate']);
            Route::get('/profiles', [BillingTaxController::class, 'profiles']);
            Route::post('/profiles', [BillingTaxController::class, 'storeProfile']);
            Route::post('/documents/{document}/issue', [BillingTaxController::class, 'issueInvoice']);
            Route::post('/reports/vat', [BillingTaxController::class, 'vatReport']);
            Route::post('/reports/tds', [BillingTaxController::class, 'tdsReport']);
            Route::get('/reports', [BillingTaxController::class, 'reports']);
            Route::get('/audits', [BillingTaxController::class, 'audits']);
        });

        // Banking, Cash, and Reconciliation
        Route::prefix('banking')->group(function () {
            Route::get('/dashboard', [BankingController::class, 'dashboard']);
            Route::get('/masters', [BankingController::class, 'masters']);
            Route::get('/bank-accounts', [BankingController::class, 'bankAccounts']);
            Route::post('/bank-accounts', [BankingController::class, 'storeBankAccount']);
            Route::get('/statements', [BankingController::class, 'statements']);
            Route::post('/statements', [BankingController::class, 'storeStatement']);
            Route::get('/book', [BankingController::class, 'book']);
            Route::post('/reconcile', [BankingController::class, 'reconcile']);
            Route::get('/reconciliations', [BankingController::class, 'reconciliations']);
            Route::get('/petty-cash-funds', [BankingController::class, 'pettyCashFunds']);
            Route::post('/petty-cash-funds', [BankingController::class, 'storePettyCashFund']);
            Route::get('/petty-cash-transactions', [BankingController::class, 'pettyCashTransactions']);
            Route::post('/petty-cash-transactions', [BankingController::class, 'storePettyCashTransaction']);
        });

        // Advanced Finance: Fixed Assets, Loans, Cost Centers, Projects, and Budgets
        Route::prefix('advanced-finance')->group(function () {
            Route::get('/dashboard', [AdvancedFinanceController::class, 'dashboard']);
            Route::get('/masters', [AdvancedFinanceController::class, 'masters']);
            Route::get('/asset-categories', [AdvancedFinanceController::class, 'assetCategories']);
            Route::post('/asset-categories', [AdvancedFinanceController::class, 'storeAssetCategory']);
            Route::get('/fixed-assets', [AdvancedFinanceController::class, 'fixedAssets']);
            Route::post('/fixed-assets', [AdvancedFinanceController::class, 'storeFixedAsset']);
            Route::post('/fixed-assets/import-hrm', [AdvancedFinanceController::class, 'importHrmAsset']);
            Route::get('/depreciation-runs', [AdvancedFinanceController::class, 'depreciationRuns']);
            Route::post('/depreciation-runs', [AdvancedFinanceController::class, 'runDepreciation']);
            Route::get('/loans', [AdvancedFinanceController::class, 'loans']);
            Route::post('/loans', [AdvancedFinanceController::class, 'storeLoan']);
            Route::get('/cost-centers', [AdvancedFinanceController::class, 'costCenters']);
            Route::post('/cost-centers', [AdvancedFinanceController::class, 'storeCostCenter']);
            Route::get('/projects', [AdvancedFinanceController::class, 'projects']);
            Route::post('/projects', [AdvancedFinanceController::class, 'storeProject']);
            Route::get('/budgets', [AdvancedFinanceController::class, 'budgets']);
            Route::post('/budgets', [AdvancedFinanceController::class, 'storeBudget']);
            Route::get('/budgets/{budget}/variance', [AdvancedFinanceController::class, 'budgetVariance']);
        });

        // Payroll and HRM Accounting Integration
        Route::prefix('payroll-accounting')->group(function () {
            Route::get('/dashboard', [PayrollAccountingController::class, 'dashboard']);
            Route::get('/masters', [PayrollAccountingController::class, 'masters']);
            Route::get('/settings', [PayrollAccountingController::class, 'settings']);
            Route::post('/settings', [PayrollAccountingController::class, 'saveSettings']);
            Route::get('/runs', [PayrollAccountingController::class, 'runs']);
            Route::post('/runs', [PayrollAccountingController::class, 'generateRun']);
            Route::post('/runs/{run}/post', [PayrollAccountingController::class, 'postRun']);
            Route::post('/runs/{run}/lock', [PayrollAccountingController::class, 'lockRun']);
            Route::post('/runs/{run}/reverse', [PayrollAccountingController::class, 'reverseRun']);
        });

        // Financial Reports and Dashboards
        Route::prefix('financial-reports')->group(function () {
            Route::get('/dashboard', [FinancialReportController::class, 'dashboard']);
            Route::get('/masters', [FinancialReportController::class, 'masters']);
            Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance']);
            Route::get('/general-ledger', [FinancialReportController::class, 'generalLedger']);
            Route::get('/profit-and-loss', [FinancialReportController::class, 'profitAndLoss']);
            Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
            Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow']);
        });
    });
});
