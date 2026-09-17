<?php

namespace App\Services\Insurance;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InsuranceService
{
    protected string $baseUrl;

    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.insurance.base_url'),'/');

        $this->secretKey = config('services.insurance.secret_key');
    }

    /**
     * Initiate an insurance request.
     *
     * POST /v1/insurance-requests
     */
    public function initiate(array $payload): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($this->secretKey)
                ->timeout(30)
                ->post(
                    $this->baseUrl . '/v1/insurance-requests',
                    $payload
                );

            $response->throw();

            return $response->json();

        } catch (RequestException $e) {

            $response = $e->response;

            throw new RuntimeException(
                $response
                    ? $response->body()
                    : $e->getMessage()
            );
        }
    }
}