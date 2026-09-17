<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantConfigController extends Controller
{
    /**
     * GET /api/tenant/config
     *
     * Public endpoint — returns tenant branding + feature flags.
     * Rate-limited to 30 req/min via route middleware.
     *
     * Slug resolution order:
     *   1. X-Tenant-Slug request header
     *   2. ?slug query parameter
     *   3. Fallback: FixPay platform defaults (pre-login state)
     *
     * Security:
     *  - Unknown slug returns generic 404 (no hint that tenant exists or not)
     *  - Response never contains tenant UUID, financial data, or internal IDs
     *  - Read-only; no state mutations
     */
    public function show(Request $request): JsonResponse
    {
        $slug = $request->header('X-Tenant-Slug')
            ?? $request->query('slug');

        // No slug provided → return safe platform defaults (pre-login state)
        if (! $slug) {
            return response()->json([
                'success' => true,
                'message' => 'OK',
                'data'    => $this->defaults(),
            ]);
        }

        $tenant = Tenant::where('slug', $slug)
            ->whereIn('status', ['ACTIVE', 'SANDBOX'])
            ->first();

        // Unknown or inactive slug → opaque 404 (no "tenant not found" leak)
        if (! $tenant) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $branding      = $tenant->branding      ?? [];
        $featureFlags  = $tenant->feature_flags ?? [];

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                // Use slug as the public tenant identifier — never the UUID
                'tenantId'        => $tenant->slug,
                'slug'            => $tenant->slug,
                'appName'         => $branding['appName']        ?? $tenant->name,
                'primaryColor'    => $branding['primaryColor']   ?? '#A51D21',
                'secondaryColor'  => $branding['secondaryColor'] ?? '#34C759',
                'accentColor'     => $branding['accentColor']    ?? '#FF9500',
                'logoUrl'         => $branding['logoUrl']        ?? null,
                'faviconUrl'      => $branding['faviconUrl']     ?? null,
                'supportEmail'    => $branding['supportEmail']   ?? 'support@fixpay.com',
                'supportPhone'    => $branding['supportPhone']   ?? '07000000000',
                'features'        => [
                    'billPayments'         => $featureFlags['billPayments']         ?? true,
                    'directDebit'          => $featureFlags['directDebit']          ?? true,
                    'walletTransfers'      => $featureFlags['walletTransfers']      ?? true,
                    'internationalAirtime' => $featureFlags['internationalAirtime'] ?? false,
                    'disputeManagement'    => $featureFlags['disputeManagement']    ?? true,
                    'nibssTransfers'       => $featureFlags['nibssTransfers']       ?? true,
                ],
            ],
        ]);
    }

    /** Safe platform-level defaults — returned before user logs in */
    private function defaults(): array
    {
        return [
            'tenantId'        => 'default',
            'slug'            => 'fixpay',
            'appName'         => 'FixPay',
            'primaryColor'    => '#A51D21',
            'secondaryColor'  => '#34C759',
            'accentColor'     => '#FF9500',
            'logoUrl'         => null,
            'faviconUrl'      => null,
            'supportEmail'    => 'support@fixpay.com',
            'supportPhone'    => '07000000000',
            'features'        => [
                'billPayments'         => true,
                'directDebit'          => true,
                'walletTransfers'      => true,
                'internationalAirtime' => false,
                'disputeManagement'    => true,
                'nibssTransfers'       => true,
            ],
        ];
    }
}
