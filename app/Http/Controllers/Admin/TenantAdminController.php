<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantKybSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TenantAdminController extends Controller
{
    /** GET /api/admin/tenants */
    public function index(Request $request): JsonResponse
    {
        $tenants = QueryBuilder::for(Tenant::class)
            ->allowedFilters(['status', 'plan', 'kyb_status'])
            ->allowedSorts(['created_at', 'name'])
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($tenants);
    }

    /** GET /api/admin/tenants/{id} */
    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::with(['kybSubmission', 'settlementAccount'])->findOrFail($id);

        return response()->json($tenant);
    }

    /** PUT /api/admin/tenants/{id}/status */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:SANDBOX,ACTIVE,SUSPENDED,OFFBOARDED',
        ]);

        $tenant = Tenant::findOrFail($id);
        $updates = ['status' => $data['status']];

        if ($data['status'] === 'ACTIVE') {
            $updates['activated_at'] = now();
        } elseif ($data['status'] === 'SUSPENDED') {
            $updates['suspended_at'] = now();
        }

        $tenant->update($updates);

        return response()->json(['message' => "Tenant status updated to {$data['status']}."]);
    }

    /** PUT /api/admin/tenants/{id}/kyb */
    public function reviewKyb(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status'        => 'required|in:APPROVED,REJECTED',
            'review_notes'  => 'nullable|string',
        ]);

        $tenant = Tenant::findOrFail($id);
        $submission = TenantKybSubmission::where('tenant_id', $id)->firstOrFail();

        $submission->update([
            'status'        => $data['status'],
            'review_notes'  => $data['review_notes'] ?? null,
            'reviewed_by'   => $request->user()?->id,
            'reviewed_at'   => now(),
        ]);

        $tenant->update(['kyb_status' => $data['status']]);

        if ($data['status'] === 'APPROVED') {
            $tenant->update(['status' => 'ACTIVE', 'activated_at' => now()]);
        }

        return response()->json(['message' => "KYB {$data['status']}."]);
    }
}
