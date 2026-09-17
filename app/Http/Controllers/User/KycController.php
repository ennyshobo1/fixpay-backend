<?php

namespace App\Http\Controllers\User;
use App\Services\VerifyMeService;
use Illuminate\Support\Facades\Crypt;

use App\Contracts\Kyc\AmlProviderInterface;
use App\Contracts\Kyc\KycProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class KycController extends Controller
{
    public function __construct(
        private readonly KycProviderInterface $kyc,
        private readonly AmlProviderInterface $aml,
        protected VerifyMeService $kycService
    ) {
        $this->kycService = $kycService;
    }

    /** POST /api/kyc/bvn */
    public function verifyBvn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bvn' => 'required|integer',
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
        ]);

        $user = $request->user();

        $existing = KycVerification::where('user_id', $user->id)->where('type', 'BVN')->where('verification_status', 'VERIFIED')->first();
        if ($existing) {
            return response()->json([
                'status' => 'VERIFIED',
                'message' => 'BVN already verified.',
            ]);
        }

        $record = KycVerification::create([
            'user_id' => $user->id,
            'type' => 'BVN',
            'identifier' => Crypt::encryptString($data['bvn']),
            'provider' => $this->kyc->getProviderName(),
            'verification_status' => 'PENDING',
        ]);

        try {

            $result = $this->kycService->verifyBvn($data['bvn'], $data['first_name'], $data['last_name']);

            $status = $result['data']['status']['status']
                ? 'VERIFIED'
                : 'FAILED';

            $record->update([

                'verification_status' => $status,

                'provider_reference' => $result['reference'],

                'response_json' => $result['data'],

                'verified_at' => $status == 'VERIFIED'
                    ? now()
                    : null,

            ]);

            if (
                $status == 'VERIFIED' &&
                $user->kyc_status == 'UNVERIFIED'
            ) {

                $user->update([
                    'kyc_status' => 'PENDING'
                ]);

            }

            return response()->json([

                'status' => $status,

                'message' => $status == 'VERIFIED'
                    ? 'BVN verified successfully.'
                    : 'BVN verification failed.',

                'data' => $result['data']

            ]);

        } catch (\Throwable $e) {

            $record->update([

                'verification_status' => 'FAILED',

                'failure_reason' => $e->getMessage()

            ]);

            return response()->json([

                'message' => $e->getMessage()

            ], 503);
        }
    }

    /** POST /api/kyc/nin */
    public function verifyNin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nin' => 'required|integer',
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',        
        ]);

        $user = $request->user();

        $existing = KycVerification::where('user_id', $user->id)->where('type', 'NIN')->where('verification_status', 'VERIFIED')->first();
        if ($existing) {
            return response()->json(['status' => 'VERIFIED']);
        }

        $record = KycVerification::create([
            'user_id' => $user->id,
            'type' => 'NIN',
            'identifier' => Crypt::encryptString($data['nin']),
            'provider' => $this->kyc->getProviderName(),
            'verification_status' => 'PENDING',
        ]);

        try {

            $result = $this->kycService->verifyNin($data['nin'], $data['first_name'], $data['last_name']);

            $status = $result['data']['status']['status']
                ? 'VERIFIED'
                : 'FAILED';

            $record->update([

                'verification_status' => $status,

                'provider_reference' => $result['reference'],

                'response_json' => $result['data'],

                'verified_at' => $status == 'VERIFIED'
                    ? now()
                    : null,

            ]);

            return response()->json([

                'status' => $status,

                'data' => $result['data']

            ]);

        } catch (\Throwable $e) {

            $record->update([

                'verification_status' => 'FAILED',

                'failure_reason' => $e->getMessage()

            ]);

            return response()->json([

                'message' => $e->getMessage()

            ], 503);
        }
    }

    /** GET /api/kyc/status */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $verifications = KycVerification::where('user_id', $user->id)->get();

        return response()->json([
            'kyc_status' => $user->kyc_status,
            'tier' => $user->tier,
            'verifications' => $verifications->map(fn ($v) => [
                'type' => $v->type,
                'status' => $v->verification_status,
                'provider' => $v->provider,
                'verified_at' => $v->verified_at,
            ]),
        ]);
    }
}
