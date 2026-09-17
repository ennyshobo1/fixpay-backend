<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRailConfig;
use App\Models\ProcessorFeeSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRailAdminController extends Controller
{
    /** GET /api/admin/payment-rails */
    public function index(): JsonResponse
    {
        return response()->json(
            PaymentRailConfig::with('feeSchedules')->get()
        );
    }

    /** POST /api/admin/payment-rails */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'processor_id'   => 'required|string|max:50',
            'priority'       => 'required|integer|min:0',
            'enabled'        => 'required|boolean',
            'maintenance'    => 'required|boolean',
            'config_json'    => 'nullable|array',
            'tenant_id'      => 'nullable|uuid',
        ]);

        $config = PaymentRailConfig::create($data);

        return response()->json($config, 201);
    }

    /** PUT /api/admin/payment-rails/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $config = PaymentRailConfig::findOrFail($id);
        $config->update($request->only(['enabled', 'maintenance', 'priority', 'config_json']));

        return response()->json($config);
    }

    /** POST /api/admin/payment-rails/{id}/fee-schedules */
    public function addFeeSchedule(Request $request, string $id): JsonResponse
    {
        $config = PaymentRailConfig::findOrFail($id);

        $data = $request->validate([
            'min_amount_kobo'  => 'required|integer|min:0',
            'max_amount_kobo'  => 'nullable|integer|min:0',
            'percentage_fee'   => 'required|numeric|min:0|max:1',
            'flat_fee_kobo'    => 'required|integer|min:0',
            'cap_kobo'         => 'nullable|integer|min:0',
            'effective_from'   => 'nullable|date',
            'effective_to'     => 'nullable|date',
        ]);

        $schedule = $config->feeSchedules()->create($data);

        return response()->json($schedule, 201);
    }
}
