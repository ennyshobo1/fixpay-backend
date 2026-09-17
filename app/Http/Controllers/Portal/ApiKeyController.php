<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /** GET /api/portal/api-keys */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('__tenant_id');
        $keys = ApiKey::where('tenant_id', $tenantId)
            ->whereNull('revoked_at')
            ->get(['id', 'name', 'environment', 'key_prefix', 'scopes', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json($keys);
    }

    /** POST /api/portal/api-keys */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:80',
            'environment' => 'required|in:TEST,LIVE',
            'scopes'      => 'nullable|array',
            'scopes.*'    => 'string',
        ]);

        $tenantId = $request->input('__tenant_id');
        $env = strtolower($data['environment']);
        $prefix = "fpk_{$env}_";
        $rawKey = $prefix . Str::random(48);

        $apiKey = ApiKey::create([
            'tenant_id'   => $tenantId,
            'name'        => $data['name'],
            'environment' => $data['environment'],
            'key_prefix'  => $prefix,
            'key_hash'    => hash('sha256', $rawKey),
            'scopes'      => $data['scopes'] ?? ['*'],
        ]);

        // Return raw key ONCE — not stored in plain text
        return response()->json([
            'id'          => $apiKey->id,
            'name'        => $apiKey->name,
            'environment' => $apiKey->environment,
            'key'         => $rawKey, // shown only once
            'scopes'      => $apiKey->scopes,
            'created_at'  => $apiKey->created_at,
        ], 201);
    }

    /** DELETE /api/portal/api-keys/{id} */
    public function revoke(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->input('__tenant_id');
        $key = ApiKey::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();
        $key->update(['revoked_at' => now()]);

        return response()->json(['message' => 'API key revoked.']);
    }
}
