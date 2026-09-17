<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\IpWhitelistRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpWhitelistController extends Controller
{
    /** GET /api/portal/ip-whitelist */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        return response()->json(IpWhitelistRule::where('tenant_id', $tenant->id)->get());
    }

    /** POST /api/portal/ip-whitelist */
    public function create(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $data = $request->validate([
            'cidr'        => 'required|string|max:50',
            'label'       => 'nullable|string|max:100',
            'environment' => 'nullable|in:sandbox,live,both',
        ]);

        // Validate CIDR format
        if (!$this->isValidCidr($data['cidr'])) {
            return response()->json(['message' => 'Invalid CIDR notation.'], 422);
        }

        $rule = IpWhitelistRule::create(array_merge($data, [
            'tenant_id'   => $tenant->id,
            'environment' => $data['environment'] ?? 'both',
        ]));

        return response()->json($rule, 201);
    }

    /** DELETE /api/portal/ip-whitelist/{id} */
    public function delete(Request $request, string $id): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $rule = IpWhitelistRule::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $rule->delete();

        return response()->json(['message' => 'IP rule removed.']);
    }

    private function isValidCidr(string $cidr): bool
    {
        // Accept bare IP or CIDR
        if (filter_var($cidr, FILTER_VALIDATE_IP)) {
            return true;
        }
        if (!str_contains($cidr, '/')) {
            return false;
        }
        [$ip, $prefix] = explode('/', $cidr, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $prefix = (int) $prefix;
        $isV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        return $prefix >= 0 && $prefix <= ($isV6 ? 128 : 32);
    }
}
