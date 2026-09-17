<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    /** GET /api/admin/users */
    public function index(Request $request): JsonResponse
    {
        $query = AppUser::with('tenant')->orderBy('created_at', 'desc');

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $users = $query->paginate(50);

        return response()->json($users);
    }

    /** GET /api/admin/users/{id} */
    public function show($id): JsonResponse
    {
        $user = AppUser::with(['tenant', 'wallet', 'kycVerifications'])->findOrFail($id);
        return response()->json($user);
    }

    /** PUT /api/admin/users/{id}/status */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:ACTIVE,SUSPENDED,BLOCKED',
        ]);

        $user = AppUser::findOrFail($id);
        $user->update(['status' => $data['status']]);

        return response()->json(['message' => 'User status updated successfully', 'user' => $user]);
    }
}
