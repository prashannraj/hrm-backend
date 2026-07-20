<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get organization settings
     */
    public function index()
    {
        $settings = OrganizationSetting::first();
        if (!$settings) {
            // Return defaults if not seeded yet
            return response()->json([
                'name' => 'Glow Forward Foundation',
                'acronym' => 'GFF',
                'registeredAddress' => 'Sanepa Heights, Lalitpur, Nepal',
                'email' => 'info@glowforward.org',
                'phone' => '+977-1-5523019',
                'registrationNo' => 'Reg-39201/SWC-9201',
                'fiscalYear' => '2025/2026',
                'departments' => ['Executive', 'Programs', 'Finance', 'Human Resources', 'M&E', 'Operations'],
                'designations' => ['Executive Director', 'Finance & Admin Director', 'Senior Program Coordinator', 'M&E Specialist', 'HR Officer', 'Program Associate', 'Finance Assistant', 'Head Driver', 'Office Assistant'],
                'leavePolicies' => [
                    ['type' => 'Casual Leave', 'allocation' => 12, 'cashable' => false],
                    ['type' => 'Sick Leave', 'allocation' => 15, 'cashable' => true],
                    ['type' => 'Maternity Leave', 'allocation' => 60, 'cashable' => false],
                    ['type' => 'Paternity Leave', 'allocation' => 10, 'cashable' => false],
                    ['type' => 'Special Leave', 'allocation' => 6, 'cashable' => false]
                ],
                'logoUrl' => null,
                'logoThumbUrl' => null,
                'ssoConfig' => [
                    'provider' => 'Microsoft Entra ID (Azure AD)',
                    'clientId' => 'appan-hrm-enterprise-client-id-8839',
                    'metadataUrl' => 'https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration',
                    'tenantId' => 'appan-technology-tenant-90291',
                    'ssoEnabled' => true,
                ],
            ]);
        }
        return response()->json($settings);
    }

    /**
     * Update organization settings
     */
    public function update(Request $request)
    {
        $settings = OrganizationSetting::first();
        if (!$settings) {
            $settings = new OrganizationSetting();
        }

        $payload = $request->all();
        if (isset($payload['logoUrl']) && $payload['logoUrl'] === '') {
            $payload['logoUrl'] = null;
        }
        if (isset($payload['logoThumbUrl']) && $payload['logoThumbUrl'] === '') {
            $payload['logoThumbUrl'] = null;
        }
        $settings->fill($payload);
        $settings->save();

        AuditLog::create([
            'timestamp' => now(),
            'user' => 'Super Admin',
            'action' => 'Updated general NGO organization settings & policies',
            'module' => 'SETTINGS'
        ]);

        return response()->json($settings);
    }

    /**
     * Fetch System Audit Logs
     */
    public function auditLogs()
    {
        return response()->json(AuditLog::orderBy('timestamp', 'desc')->get());
    }
}
