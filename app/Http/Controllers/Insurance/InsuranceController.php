<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;

use App\Http\Requests\InitiateInsuranceRequest;
use App\Models\Insurance;
use App\Services\Insurance\InsuranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InsuranceController extends Controller
{
    public function __construct(
        protected InsuranceService $insuranceService
    ) {
    }

    /**
     * Initiate insurance purchase.
     *
     * POST /v1/insurance
     */
    public function getInsurance(InitiateInsuranceRequest $request): JsonResponse {
       
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validated();

        /*
         * The callback URL must point back to FixPay.
         */

        /*
         * Build the payload expected by the insurance API.
         */
        $payload = [
            'email' => $user->email,
            'phone' => $user->phone,

            'product_type' => $validated['product_type'],
            'product_variant' => $validated['product_variant'],

            'nin' => $validated['nin'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? null,
            'plate_number' => $validated['plate_number'] ?? null,

            'callback_url' => null,
        ];

        try {
            /*
             * Call:
             *
             * POST /v1/insurance-requests
             */
            $providerResponse = $this->insuranceService->initiate($payload);

            /*
             * Make sure the provider returned data.
             */
            if (
                !isset($providerResponse['data']) ||
                !is_array($providerResponse['data'])
            ) {
                Log::error('Invalid insurance provider response', [
                    'response' => $providerResponse,
                ]);

                return response()->json([
                    'message' => 'Invalid response received from insurance provider.',
                ], 502);
            }

            $data = $providerResponse['data'];

            /*
             * Reference is essential because we use it to
             * identify the insurance transaction later.
             */
            if (empty($data['reference'])) {
                Log::error('Insurance provider response missing reference', [
                    'response' => $providerResponse,
                ]);

                return response()->json([
                    'message' => 'Insurance provider did not return a reference.',
                ], 502);
            }

            /*
             * Save the insurance transaction.
             */
            $insurance = DB::transaction(function () use (
                $user,
                $validated,
                $payload,
                $data
            ) {
                return Insurance::create([
                    
                    'user_id' => $user->id,

                    'email' => $payload['email'],
                    
                    'phone' => $payload['phone'],

                    'product_type' => $validated['product_type'],
                    
                    'product_variant' => $validated['product_variant'],

                    'nin' => $validated['nin'] ?? null,
                    
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    
                    'plate_number' => $validated['plate_number'] ?? null,

                    'reference' => $data['reference'],

                    'customer_id' => $data['customer_id'] ?? null,

                    'premium_amount' => $data['premium_amount'] ?? 0,

                    'currency' => $data['currency'] ?? 'NGN',

                    'status' => $data['status'] ?? 'pending',

                    'checkout_url' => $data['checkout_url'] ?? null,

                    'expires_at' => !empty($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at'])->format('Y-m-d H:i:s') : null,

                    'callback_url' => null,
                ]);
            });

            return response()->json([
                'message' => 'Insurance request initiated successfully.',

                'data' => [
                    'id' => $insurance->id,
                    'reference' => $insurance->reference,
                    'checkout_url' => $insurance->checkout_url,
                    'status' => $insurance->status,
                    'expires_at' => $insurance->expires_at,
                    'premium_amount' => $insurance->premium_amount,
                    'currency' => $insurance->currency,
                ],
            ], 201);

        } catch (Throwable $e) {

            Log::error('Insurance initiation failed', [
                'user_id' => $user->id,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to initiate insurance request.',
            ], 502);
        }
    }
}
