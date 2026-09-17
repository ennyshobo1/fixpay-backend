<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemAdminController extends Controller
{
    /** GET /api/admin/system/health */
    public function health(Request $request): JsonResponse
    {
        $dbStatus = 'OK';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'DOWN';
        }

        // Mock third-party rails health
        $thirdPartyRails = [
            ['name' => 'Paystack', 'status' => 'OPERATIONAL', 'latency_ms' => random_int(50, 150)],
            ['name' => 'VTPass', 'status' => 'OPERATIONAL', 'latency_ms' => random_int(100, 300)],
            ['name' => 'NIBSS', 'status' => 'DEGRADED', 'latency_ms' => random_int(400, 1200)],
        ];

        return response()->json([
            'status' => ($dbStatus === 'OK') ? 'OPERATIONAL' : 'SYSTEM_ISSUE',
            'components' => [
                'database' => $dbStatus,
                // App memory usage in MB
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ],
            'third_party_rails' => $thirdPartyRails,
        ]);
    }
}
