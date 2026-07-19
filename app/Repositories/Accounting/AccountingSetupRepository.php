<?php

namespace App\Repositories\Accounting;

use App\Models\ErpAccount;
use App\Models\ErpAccountGroup;
use App\Models\ErpAccountingSetting;
use App\Models\ErpCompany;

class AccountingSetupRepository
{
    public function defaultCompany(): ?ErpCompany
    {
        return ErpCompany::query()->where('is_active', true)->orderBy('id')->first();
    }

    public function settingsForCompany(int $companyId): ?ErpAccountingSetting
    {
        return ErpAccountingSetting::query()
            ->with(['defaultBranch', 'currentFiscalYear'])
            ->where('company_id', $companyId)
            ->first();
    }

    public function accountGroups(int $companyId)
    {
        return ErpAccountGroup::query()
            ->with('children')
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function accounts(int $companyId)
    {
        return ErpAccount::query()
            ->with(['group', 'children'])
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get();
    }
}
