<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyMeService
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.qoreid.base_url');
        $this->clientId = config('services.qoreid.client_id');
        $this->secret = config('services.qoreid.secret');
    }

    public function getProviderName(): string
    {
        return 'VerifyMe';
    }

    /**
     * Get VerifyMe Access Token
     */
    protected function getToken(): string
    {
        $response = Http::withoutVerifying()
        ->acceptJson()
        ->post($this->baseUrl.'/token', [
            'clientId' => $this->clientId,
            'secret'   => $this->secret,
        ]);

        if (!$response->successful()) {

            throw new \Exception(
                'Unable to authenticate with VerifyMe.'
            );

        }

        return $response->json('accessToken');
    }

    /**
     * Verify BVN
     */
    public function verifyBvn(int $bvn, string $first_name, string $last_name): array
    {

        $token = $this->getToken();

        $parts = explode('.', $token);

        Log::info(json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true));
        $token = $this->getToken();

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->acceptJson()
            ->post(
                $this->baseUrl."/v1/ng/identities/bvn-basic/{$bvn}",
                [
                    'firstname' => $first_name,
                    'lastname'  => $last_name,
                ]
            );

        if (!$response->successful()) {

            throw new \Exception($response->body());

        }

        $data = $response->json();

        return [

            'status' => true,

            'reference' => $data['requestId'] ?? uniqid(),

            'data' => $data

        ];
    }

    /**
     * Verify NIN
     */
    public function verifyNin(int $nin, string $first_name, string $last_name): array
    {
        $token = $this->getToken();

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->acceptJson()
            ->post(
                $this->baseUrl."/v1/ng/identities/nin/{$nin}",
                [
                    'firstname' => $first_name,
                    'lastname'  => $last_name,
                ]
            );

        if (!$response->successful()) {

            throw new \Exception($response->body());

        }

        $data = $response->json();

        return [

            'status' => true,

            'reference' => $data['requestId'] ?? uniqid(),

            'data' => $data

        ];
    }
}