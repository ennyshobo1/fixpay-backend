<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FraudAdminController extends Controller
{
    /** GET /api/admin/fraud/rules */
    public function getRules(Request $request): JsonResponse
    {
        // Mock fraud rules
        $rules = [
            ['id' => 1, 'name' => 'High Volume Transfer', 'threshold' => 100000000, 'action' => 'FLAG', 'status' => 'ACTIVE'],
            ['id' => 2, 'name' => 'Rapid Successive Transfers', 'threshold' => 5, 'timeframe_minutes' => 10, 'action' => 'BLOCK', 'status' => 'ACTIVE'],
        ];

        return response()->json($rules);
    }

    /** GET /api/admin/fraud/cases */
    public function getCases(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'total' => 0
        ]);
    }
}
