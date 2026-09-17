<?php

namespace App\Services\Kyc;

use App\Contracts\Kyc\AmlProviderInterface;

/**
 * Mock AML adapter — always returns clean (no hits).
 */
class MockAmlAdapter implements AmlProviderInterface
{
    public function screenPep(string $fullName, string $dob, string $country = 'NG'): array
    {
        return [
            'status' => true,
            'is_pep' => false,
            'reference' => 'mock_pep_' . md5($fullName . $dob),
            'matches' => [],
        ];
    }

    public function screenSanctions(string $fullName, string $country = 'NG'): array
    {
        return [
            'status' => true,
            'is_sanctioned' => false,
            'reference' => 'mock_sanctions_' . md5($fullName),
            'matches' => [],
        ];
    }

    public function getProviderName(): string
    {
        return 'mock';
    }
}
