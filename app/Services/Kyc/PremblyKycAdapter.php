<?php

namespace App\Services\Kyc;

use App\Contracts\Kyc\KycProviderInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PremblyKycAdapter implements KycProviderInterface
{
    public function __construct(
        private readonly Client $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    public function verifyBvn(string $bvn, string $dob): array
    {
        $response = $this->http->post("{$this->baseUrl}/v2/biometrics/merchant/data/verification/bvn", [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'app-id' => config('services.prembly.app_id'),
                'Content-Type' => 'application/json',
            ],
            'json' => ['number' => $bvn, 'dob' => $dob],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $data['status'] ?? false,
            'reference' => $data['verification']['reference'] ?? uniqid('prembly_'),
            'data' => $data['verification'] ?? [],
        ];
    }

    public function verifyNin(string $nin): array
    {
        $response = $this->http->post("{$this->baseUrl}/v2/biometrics/merchant/data/verification/nin", [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'app-id' => config('services.prembly.app_id'),
                'Content-Type' => 'application/json',
            ],
            'json' => ['number' => $nin],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $data['status'] ?? false,
            'reference' => $data['verification']['reference'] ?? uniqid('prembly_'),
            'data' => $data['verification'] ?? [],
        ];
    }

    public function verifyCac(string $rcNumber): array
    {
        $response = $this->http->post("{$this->baseUrl}/v2/biometrics/merchant/data/verification/cac", [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'app-id' => config('services.prembly.app_id'),
                'Content-Type' => 'application/json',
            ],
            'json' => ['rc_number' => $rcNumber],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $data['status'] ?? false,
            'reference' => $data['verification']['reference'] ?? uniqid('prembly_'),
            'data' => $data['verification'] ?? [],
        ];
    }

    public function matchFace(string $imageBase64, string $referenceId): array
    {
        $response = $this->http->post("{$this->baseUrl}/v2/biometrics/merchant/data/selfie", [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'app-id' => config('services.prembly.app_id'),
                'Content-Type' => 'application/json',
            ],
            'json' => ['image' => $imageBase64, 'number' => $referenceId],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $data['status'] ?? false,
            'confidence' => (float) ($data['verification']['confidence'] ?? 0.0),
            'reference' => $data['verification']['reference'] ?? uniqid('prembly_'),
        ];
    }

    public function getProviderName(): string
    {
        return 'prembly';
    }
}
