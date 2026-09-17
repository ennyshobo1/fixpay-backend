<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TenantKybSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KybController extends Controller
{
    /** POST /api/portal/kyb */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name'    => 'required|string|max:200',
            'cac_number'       => 'required|string|max:20',
            'tin_number'       => 'nullable|string|max:20',
            'business_type'    => 'required|in:SOLE_PROPRIETORSHIP,PARTNERSHIP,LIMITED_LIABILITY,PUBLIC_COMPANY,NGO',
            'business_address' => 'required|string|max:300',
            'website_url'      => 'nullable|url',
            'directors'        => 'nullable|array',
            'document_urls'    => 'nullable|array',
        ]);

        $tenantId = $request->input('__tenant_id');

        $submission = TenantKybSubmission::updateOrCreate(
            ['tenant_id' => $tenantId],
            array_merge($data, [
                'status' => 'SUBMITTED',
                'submitted_at' => now(),
            ])
        );

        // Update tenant kyb_status
        \App\Models\Tenant::where('id', $tenantId)->update(['kyb_status' => 'SUBMITTED']);

        return response()->json([
            'message' => 'KYB submitted for review.',
            'submission_id' => $submission->id,
        ]);
    }

    /** GET /api/portal/kyb */
    public function show(Request $request): JsonResponse
    {
        $tenantId = $request->input('__tenant_id');
        $submission = TenantKybSubmission::where('tenant_id', $tenantId)->first();

        if (! $submission) {
            return response()->json(['message' => 'No KYB submission found.'], 404);
        }

        return response()->json($submission);
    }
}
