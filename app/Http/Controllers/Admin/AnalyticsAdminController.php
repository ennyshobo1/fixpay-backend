<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsAdminController extends Controller
{
    /** GET /api/admin/analytics */
    public function index(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // KPIs
        $totalVolumeKobo = Transfer::where('status', 'SUCCESS')->sum('amount_kobo');
        $activeTenants = Tenant::where('status', 'ACTIVE')->count();
        
        $totalTransfers = Transfer::count();
        $successfulTransfers = Transfer::where('status', 'SUCCESS')->count();
        $successRate = $totalTransfers > 0 ? round(($successfulTransfers / $totalTransfers) * 100, 2) : 0;

        // Revenue could be the sum of fees
        $totalRevenueKobo = Transfer::where('status', 'SUCCESS')->sum('fee_kobo');

        // Chart Data (Last 7 days of transaction volume)
        $sevenDaysAgo = $now->copy()->subDays(7);
        $volumeChart = Transfer::where('status', 'SUCCESS')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('CAST(created_at AS DATE) as date'),
                DB::raw('SUM(amount_kobo) as volume')
            )
            ->groupBy(DB::raw('CAST(created_at AS DATE)'))
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json([
            'kpis' => [
                'total_processing_volume' => $totalVolumeKobo / 100,
                'active_tenants' => $activeTenants,
                'success_rate' => $successRate,
                'total_revenue' => $totalRevenueKobo / 100,
            ],
            'chart_data' => $volumeChart->map(fn($item) => [
                'date' => $item->date,
                'volume' => $item->volume / 100,
            ])
        ]);
    }
}
