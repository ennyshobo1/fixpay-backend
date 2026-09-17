<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantPortalController extends Controller
{
    /** GET /api/portal/profile */
    public function profile(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        return response()->json($tenant->load('settlementAccount', 'kybSubmission'));
    }

    /** PUT /api/portal/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $data = $request->validate([
            'name'     => 'sometimes|string|max:150',
            'email'    => 'sometimes|email|max:191',
            'phone'    => 'sometimes|string|max:20',
            'branding' => 'sometimes|array',
        ]);

        $tenant->update($data);

        return response()->json($tenant->fresh());
    }

    /** POST /api/portal/go-live */
    public function requestGoLive(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ($tenant->kyb_status !== 'APPROVED') {
            return response()->json(['message' => 'KYB must be approved before going live.'], 422);
        }

        if ($tenant->go_live_requested_at) {
            return response()->json(['message' => 'Go-live request already submitted.'], 409);
        }

        $tenant->update(['go_live_requested_at' => now()]);

        return response()->json(['message' => 'Go-live request submitted. Our team will review and activate your account.']);
    }
}
