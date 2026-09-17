<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve tenant from subdomain, header, or API key context
        $tenantId = $request->input('__tenant_id')
            ?? $request->header('X-Tenant-Id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->status !== 'OFFBOARDED') {
                $request->merge(['__tenant' => $tenant]);
                $request->attributes->set('current_tenant', $tenant);
                app()->instance('current_tenant', $tenant);
            }
        }

        return $next($request);
    }
}
