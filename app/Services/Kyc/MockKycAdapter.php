<?php

namespace App\Services\Kyc;

use App\Contracts\Kyc\KycProviderInterface;

/**
 * Mock KYC adapter — always returns deterministic success.
 * Used when KYC_PROVIDER=mock (development/sandbox).
 */
class MockKycAdapter implements KycProviderInterface
{
    public function verifyBvn(string $bvn, string $dob): array
    {
        return [
            'status' => true,
            'reference' => 'mock_bvn_' . $bvn,
            'data' => [
                'bvn' => $bvn,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'dob' => $dob,
                'phone' => '08000000000',
            ],
        ];
    }

    public function verifyNin(string $nin): array
    {
        return [
            'status' => true,
            'reference' => 'mock_nin_' . $nin,
            'data' => [
                'nin' => $nin,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ];
    }

    public function verifyCac(string $rcNumber): array
    {
        return [
            'status' => true,
            'reference' => 'mock_cac_' . $rcNumber,
            'data' => [
                'rc_number' => $rcNumber,
                'company_name' => 'Mock Company Ltd',
                'status' => 'ACTIVE',
            ],
        ];
    }

    public function matchFace(string $imageBase64, string $referenceId): array
    {
        return [
            'status' => true,
            'confidence' => 0.99,
            'reference' => 'mock_selfie_' . $referenceId,
        ];
    }

    public function getProviderName(): string
    {
        return 'mock';
    }
}
