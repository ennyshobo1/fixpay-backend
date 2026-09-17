<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortalRegistrationController extends Controller
{
    /** POST /api/portal/register */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:150',
            'email'         => 'required|email|unique:tenants,email',
            'phone'         => 'nullable|string|max:20',
        ]);

        $slug = Str::slug($data['business_name']) . '-' . Str::random(6);

        $tenant = Tenant::create([
            'name'       => $data['business_name'],
            'slug'       => strtolower($slug),
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'status'     => 'SANDBOX',
            'plan'       => 'STARTER',
            'kyb_status' => 'NONE',
        ]);

        return response()->json([
            'message'   => 'Portal account created. Submit KYB to go live.',
            'tenant_id' => $tenant->id,
            'slug'      => $tenant->slug,
        ], 201);
    }
}
