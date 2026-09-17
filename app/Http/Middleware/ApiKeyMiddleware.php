<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-API-Key') ?? $request->bearerToken();

        if (! $rawKey) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        // Key format: fpk_test_<base64> or fpk_live_<base64>
        $prefix = substr($rawKey, 0, 9); // e.g., fpk_test_
        $keyHash = hash('sha256', $rawKey);

        $apiKey = ApiKey::where('key_hash', $keyHash)->first();

        if (! $apiKey || ! $apiKey->isActive()) {
            return response()->json(['message' => 'Invalid or expired API key.'], 401);
        }

        $apiKey->update(['last_used_at' => now()]);
        $request->merge(['__tenant_id' => $apiKey->tenant_id, '__api_key' => $apiKey]);

        return $next($request);
    }
}
