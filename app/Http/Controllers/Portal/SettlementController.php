<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SettlementAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    /** GET /api/portal/settlement */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $account = SettlementAccount::where('tenant_id', $tenant->id)->first();

        return response()->json($account);
    }

    /** POST /api/portal/settlement */
    public function upsert(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $data = $request->validate([
            'bank_code'      => 'required|string|max:10',
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|size:10',
            'account_name'   => 'required|string|max:150',
        ]);

        $account = SettlementAccount::updateOrCreate(
            ['tenant_id' => $tenant->id],
            array_merge($data, ['verified_at' => null]) // reset verification on update
        );

        return response()->json($account, $account->wasRecentlyCreated ? 201 : 200);
    }
}
