<?php

namespace App\Http\Controllers\Mandate;

use App\Http\Controllers\Controller;
use App\Models\NibssMandate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MandateController extends Controller
{
    /** GET /api/mandates */
    public function index(Request $request): JsonResponse
    {
        $mandates = NibssMandate::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($mandates);
    }

    /** POST /api/mandates */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mandate_type'      => 'required|in:RECURRING,ONETIME',
            'amount_kobo'       => 'required|integer|min:100',
            'frequency'         => 'required|in:DAILY,WEEKLY,MONTHLY,QUARTERLY,ANNUALLY',
            'start_date'        => 'required|date|after_or_equal:today',
            'end_date'          => 'nullable|date|after:start_date',
            'debtor_account'    => 'required|string|size:10',
            'debtor_bank_code'  => 'required|string|max:10',
            'narration'         => 'nullable|string|max:200',
        ]);

        $user = $request->user();

        $mandate = NibssMandate::create(array_merge($data, [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'status'     => 'PENDING',
            'reference'  => 'MND-' . strtoupper(substr(md5(uniqid()), 0, 12)),
        ]));

        return response()->json($mandate, 201);
    }

    /** GET /api/mandates/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $mandate = NibssMandate::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($mandate);
    }

    /** DELETE /api/mandates/{id} */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $mandate = NibssMandate::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        if (!in_array($mandate->status, ['PENDING', 'ACTIVE'])) {
            return response()->json(['message' => 'Cannot cancel a mandate in status: ' . $mandate->status], 422);
        }

        $mandate->update(['status' => 'CANCELLED', 'cancelled_at' => now()]);

        return response()->json(['message' => 'Mandate cancelled.']);
    }
}
