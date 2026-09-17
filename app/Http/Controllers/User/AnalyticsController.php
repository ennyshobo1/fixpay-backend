<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LedgerEntry;
use App\Models\VtpassPayment;
use App\Models\Transfer;
use App\Models\Dispute;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        $period = $request->query('period', '30d');
        $since = $request->query('since') ?? $request->header('If-Modified-Since');

        if ($since && $wallet) {
            try {
                $sinceTime = Carbon::parse($since);
                $hasNewLedger = LedgerEntry::where('wallet_id', $wallet->id)->where('created_at', '>', $sinceTime)->exists();
                $hasNewVtpass = VtpassPayment::where('user_id', $user->id)->where('updated_at', '>', $sinceTime)->exists();
                $hasNewTransfer = Transfer::where('user_id', $user->id)->where('updated_at', '>', $sinceTime)->exists();
                $hasNewDispute = Dispute::where('user_id', $user->id)->where('updated_at', '>', $sinceTime)->exists();

                if (!$hasNewLedger && !$hasNewVtpass && !$hasNewTransfer && !$hasNewDispute) {
                    return response()->json([], 304);
                }
            } catch (\Exception $e) {
                // if parsing fails, proceed
            }
        }

        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        $incomeTotal = 0;
        $expenseTotal = 0;
        $trendData = [];
        
        if ($wallet) {
            $incomeTotal = LedgerEntry::where('wallet_id', $wallet->id)
                ->where('entry_type', 'CREDIT')
                ->where('created_at', '>=', $startDate)
                ->sum('amount_kobo');

            $expenseTotal = LedgerEntry::where('wallet_id', $wallet->id)
                ->where('entry_type', 'DEBIT')
                ->where('created_at', '>=', $startDate)
                ->sum('amount_kobo');

            $trendDataQuery = LedgerEntry::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw("SUM(CASE WHEN entry_type = 'CREDIT' THEN amount_kobo ELSE 0 END) as income"),
                    DB::raw("SUM(CASE WHEN entry_type = 'DEBIT' THEN amount_kobo ELSE 0 END) as expense")
                )
                ->where('wallet_id', $wallet->id)
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');
                
            $days = (int) $startDate->diffInDays(now());
            for ($i = $days; $i >= 0; $i--) {
                $dateString = now()->subDays((int) $i)->format('Y-m-d');
                $row = $trendDataQuery->get($dateString);
                
                $trendData[] = [
                    'date' => $dateString,
                    'income' => $row ? (int)$row->income : 0,
                    'expense' => $row ? (int)$row->expense : 0
                ];
            }
        }

        // Categories Breakdown
        $categoriesBreakdown = [];
        $vtpassBreakdown = VtpassPayment::select('service_id', DB::raw('SUM(amount_kobo) as total'))
            ->where('user_id', $user->id)
            ->where('payment_status', 'COMPLETED')
            ->where('created_at', '>=', $startDate)
            ->groupBy('service_id')
            ->get();
            
        foreach ($vtpassBreakdown as $row) {
            $categoriesBreakdown[$row->service_id] = (int)$row->total;
        }

        $transfersBreakdown = Transfer::select('transfer_type', DB::raw('SUM(amount_kobo) as total'))
            ->where('user_id', $user->id)
            ->where('status', 'COMPLETED')
            ->where('created_at', '>=', $startDate)
            ->groupBy('transfer_type')
            ->get();

        foreach ($transfersBreakdown as $row) {
            $categoriesBreakdown['Transfer_' . $row->transfer_type] = (int)$row->total;
        }

        // Disputes summary
        $disputesSummary = [
            'OPEN' => 0,
            'RESOLVED' => 0,
            'OTHER' => 0
        ];
        $disputes = Dispute::select('status', DB::raw('COUNT(*) as count'))
            ->where('user_id', $user->id)
            ->groupBy('status')
            ->get();
            
        foreach ($disputes as $row) {
            if (isset($disputesSummary[$row->status])) {
                $disputesSummary[$row->status] = (int)$row->count;
            } else {
                $disputesSummary['OTHER'] += (int)$row->count;
            }
        }

        return response()->json([
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'categories_breakdown' => $categoriesBreakdown,
            'disputes_summary' => $disputesSummary,
            'trend_data' => $trendData,
            'period' => $period,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
