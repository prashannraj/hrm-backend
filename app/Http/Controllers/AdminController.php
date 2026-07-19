<?php

namespace App\Http\Controllers;

use App\Repositories\Accounting\AccountingSetupRepository;
use App\Services\Accounting\ErpIntegrityCheckService;
use App\Services\Accounting\ErpIntegrityService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly AccountingSetupRepository $setupRepository,
        private readonly ErpIntegrityCheckService $integrityCheckService,
        private readonly ErpIntegrityService $integrityService
    ) {
    }

    public function integrityChecks(Request $request)
    {
        $context = $this->context();
        $checks = $this->integrityCheckService->runAllChecks(
            $context['company']->id,
            $context['fiscalYear']->id
        );

        return response()->json([
            'company' => $context['company'],
            'fiscal_year' => $context['fiscalYear'],
            'checks' => $checks,
        ]);
    }

    public function closeFiscalYear(Request $request)
    {
        $context = $this->context();
        $fiscalYear = $context['fiscalYear'];

        $fiscalYear->update(['is_closed' => true]);

        return response()->json([
            'message' => 'Fiscal year closed successfully',
            'fiscal_year' => $fiscalYear->fresh(),
        ]);
    }

    public function reopenFiscalYear(Request $request)
    {
        $context = $this->context();
        $fiscalYear = $context['fiscalYear'];

        $fiscalYear->update(['is_closed' => false]);

        return response()->json([
            'message' => 'Fiscal year reopened successfully',
            'fiscal_year' => $fiscalYear->fresh(),
        ]);
    }

    public function userPermissions(Request $request)
    {
        $context = $this->context();
        $user = $request->user();

        $permissions = $user->roles()
            ->where('erp_roles.company_id', $context['company']->id)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();

        return response()->json([
            'user' => $user,
            'company' => $context['company'],
            'permissions' => $permissions,
        ]);
    }

    private function context(): array
    {
        $company = $this->setupRepository->defaultCompany();
        abort_if(!$company, 422, 'ERP company is not configured. Run ERP accounting seeders.');

        $settings = $this->setupRepository->settingsForCompany($company->id);
        abort_if(!$settings || !$settings->defaultBranch || !$settings->currentFiscalYear, 422, 'ERP accounting settings are incomplete.');

        return [
            'company' => $company,
            'branch' => $settings->defaultBranch,
            'fiscalYear' => $settings->currentFiscalYear,
        ];
    }
}