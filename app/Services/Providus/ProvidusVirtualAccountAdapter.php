<?php

namespace App\Services\Providus;

use GuzzleHttp\Client;

class ProvidusVirtualAccountAdapter
{
    public function __construct(
        private readonly Client $http,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $baseUrl,
    ) {}

    public function createAccount(string $accountName, string $bvn): array
    {
        // Stub: returns a fake virtual account for dev
        if (config('services.providus.mock', true)) {
            return $this->mockAccount($accountName);
        }

        $response = $this->http->post("{$this->baseUrl}/PiPCreateReservedAccountNumber", [
            'headers' => [
                'Client-Id' => $this->clientId,
                'X-Auth-Signature' => $this->buildSignature($accountName, $bvn),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'account_name' => $accountName,
                'bvn' => $bvn,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'account_number' => $data['account_number'] ?? null,
            'bank' => 'Providus Bank',
            'bank_code' => '101',
            'reference' => $data['requestId'] ?? null,
        ];
    }

    private function mockAccount(string $accountName): array
    {
        return [
            'account_number' => '9' . str_pad((string) random_int(100_000_000, 999_999_999), 9, '0'),
            'bank' => 'Providus Bank',
            'bank_code' => '101',
            'reference' => 'mock_' . uniqid(),
        ];
    }

    private function buildSignature(string $accountName, string $bvn): string
    {
        return hash('sha512', $this->clientId . $this->clientSecret . $accountName . $bvn);
    }
}
