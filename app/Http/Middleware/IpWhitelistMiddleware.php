<?php

namespace App\Http\Middleware;

use App\Models\IpWhitelistRule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->input('__tenant_id');
        if (! $tenantId) {
            return $next($request);
        }

        $rules = IpWhitelistRule::where('tenant_id', $tenantId)
            ->where('active', true)
            ->get();

        if ($rules->isEmpty()) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($rules as $rule) {
            if ($this->ipMatchesCidr($clientIp, $rule->ip_cidr)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'IP address not whitelisted.'], 403);
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - (int) $mask)) - 1);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
