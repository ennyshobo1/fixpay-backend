<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\VtpassService;
use App\Models\VtpassPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AlternativePaymentController extends Controller
{
    public function __construct(
        private readonly VtpassService $vtpass,
    ) {}

    /** POST /api/payments/alternative/initiate */
    public function initiate(Request $request): JsonResponse
    {
        return response()->json([
            'message' => '... payment option comming soon'
        ], 400);
    }

    /** POST /api/payments/alternative/verify */
    public function verify(Request $request): JsonResponse
    {
        return response()->json([
            'message' => '... payment option comming soon'
        ], 400);
    }
}
