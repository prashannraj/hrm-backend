<?php

namespace App\Http\Middleware;

use App\Models\ErpPermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ErpPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $company = $this->getCompanyFromRequest($request);
        if (!$company) {
            abort(422, 'Company context not found');
        }

        $hasPermission = $user->roles()
            ->where('erp_roles.company_id', $company->id)
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('erp_permissions.name', $permission);
            })
            ->exists();

        if (!$hasPermission) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }

    private function getCompanyFromRequest(Request $request): ?object
    {
        // Try to get company from route parameter
        $companyId = $request->route('company');
        if ($companyId) {
            return \App\Models\ErpCompany::find($companyId);
        }

        // Try to get from user's default company
        return \App\Models\ErpCompany::where('is_default', true)->first();
    }
}