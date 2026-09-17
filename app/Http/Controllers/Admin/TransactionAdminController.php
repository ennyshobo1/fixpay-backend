<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionAdminController extends Controller
{
    /** GET /api/admin/transactions */
    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with(['user', 'wallet', 'wallet.tenant'])
            ->orderBy('created_at', 'desc');

        if ($request->has('date_from') && !empty($request->query('date_from'))) {
            $query->where('created_at', '>=', Carbon::parse($request->query('date_from'))->startOfDay());
        } elseif ($request->has('days')) {
            $days = $request->query('days', 7);
            $since = Carbon::now()->subDays((int)$days);
            $query->where('created_at', '>=', $since);
        }

        if ($request->has('date_to') && !empty($request->query('date_to'))) {
            $query->where('created_at', '<=', Carbon::parse($request->query('date_to'))->endOfDay());
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }

        if ($request->has('tenant_name') && !empty($request->query('tenant_name'))) {
            $tenantName = $request->query('tenant_name');
            $query->whereHas('wallet.tenant', function ($q) use ($tenantName) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($tenantName) . '%']);
            });
        }

        $transfers = $query->paginate(50);

        return response()->json($transfers);
    }
    
    /** GET /api/admin/transactions/ledger */
    public function ledger(Request $request): JsonResponse
    {
        $query = LedgerEntry::with(['wallet', 'wallet.tenant'])
            ->orderBy('created_at', 'desc');

        if ($request->has('date_from') && !empty($request->query('date_from'))) {
            $query->where('created_at', '>=', Carbon::parse($request->query('date_from'))->startOfDay());
        } elseif ($request->has('days')) {
            $days = $request->query('days', 7);
            $since = Carbon::now()->subDays((int)$days);
            $query->where('created_at', '>=', $since);
        }

        if ($request->has('date_to') && !empty($request->query('date_to'))) {
            $query->where('created_at', '<=', Carbon::parse($request->query('date_to'))->endOfDay());
        }

        if ($request->has('entryType') && !empty($request->query('entryType'))) {
            $query->where('entry_type', $request->query('entryType'));
        }

        if ($request->has('tenant_name') && !empty($request->query('tenant_name'))) {
            $tenantName = $request->query('tenant_name');
            $query->whereHas('wallet.tenant', function ($q) use ($tenantName) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($tenantName) . '%']);
            });
        }

        $entries = $query->paginate(100);

        return response()->json($entries);
    }
}
