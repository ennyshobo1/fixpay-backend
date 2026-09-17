<?php

namespace App\Services\Wallet;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class DigitalBankingService
{
    protected ?string $baseUrl = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;

    public function __construct()
    {
        $this->baseUrl = config('services.digital_bank.base_url');

        $this->username = config('services.digital_bank.username');

        $this->password = config('services.digital_bank.password');

        $this->clientId = config('services.digital_bank.client_id');

        $this->clientSecret = config('services.digital_bank.client_secret');
    }

    /**
     * Authenticate
     */
    public function authenticate(): array
    {
        try {

            $response = Http::acceptJson()

                ->post(
                    "{$this->baseUrl}/api/v1/authenticate",
                    [

                        'username' => $this->username,

                        'password' => $this->password,

                        'clientId' => $this->clientId,

                        'clientSecret' => $this->clientSecret,

                    ]
                );

            if (!$response->successful()) {

                throw new Exception($response->body());

            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

            throw $e;

        }
    }

    /**
     * Cached Access Token
     */
    protected function token(): string
    {
        return Cache::remember(

            'digital_bank_token',

            now()->addMinutes(110),

            function () {

                $auth = $this->authenticate();

                return $auth['accessToken'];

            }

        );
    }

    public function verifyBVN(array $data)
    {
        try {

            $image = base64_encode(
                file_get_contents($data['image']->getRealPath())
            );

            $payload = [
                'bvn' => $data['bvn'],
                'nin' => $data['nin'],
                'type' => $data['type'],
                'image' => $image,
                'phoneNo' => $data['phoneNo'],
                'transactionRef' => $data['transactionRef'],
            ];

            Log::info('Verify BVN Payload', $payload);

            $response = Http::withToken($this->token())
                ->acceptJson()
                ->post(
                    "{$this->baseUrl}/api/v1/identity/initiate",
                    $payload
                );
                // ->post(
                //     "http://102.216.128.75:9090/waas/api/v1/identity/initiate",
                //     $payload
                // );

            if (!$response->successful()) {

                throw new Exception($response->body());

            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

            throw $e;

        }
    }

    public function openWallet(array $payload): array
    {
        try {

            Log::info('Open Wallet URL', [
                'url' => $this->baseUrl . '/api/v1/open_wallet',
            ]);

            Log::info('Open Wallet Payload', $payload);

            $response = Http::withToken($this->token())

                ->acceptJson()

                ->post(

                    "{$this->baseUrl}/api/v1/open_wallet",

                    $payload

                );

            if (!$response->successful()) {

                throw new Exception($response->body());

            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

            throw $e;

        }
    }

    public function creditWallet(string $accountNumber, float $amount, string $transactionId, string $narration = 'Wallet Funding'): array 
    {
        try {

            $response = Http::withToken($this->token())
            ->acceptJson()
            ->post(

                "{$this->baseUrl}/api/v1/credit/transfer",

                [

                    'accountNo' => $accountNumber,

                    'narration' => $narration,

                    'totalAmount' => $amount,

                    'transactionId' => $transactionId,

                    'merchant' => [

                        'isFee' => false,

                        'merchantFeeAccount' => '',

                        'merchantFeeAmount' => '',

                    ],

                ]

            );

            if (!$response->successful()) {

                throw new Exception($response->body());

            }

            return $response->json();

        }

        catch (\Throwable $e) {

            Log::error($e->getMessage());

            throw $e;

        }
    }

    public function debitWallet(string $accountNumber, float $amount, string $transactionId, string $narration = 'Wallet Debit'): array
    {
        try {
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->post(
                    "{$this->baseUrl}/api/v1/debit/transfer",
                    [
                        'accountNo' => $accountNumber,
                        'narration' => $narration,
                        'totalAmount' => $amount,
                        'transactionId' => $transactionId,
                        'merchant' => [
                            'isFee' => false,
                            'merchantFeeAccount' => '',
                            'merchantFeeAmount' => '',
                        ],
                    ]
                );

                Log::info('Debit response', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('9psb debit failed', [
                'account' => $accountNumber,
                'amount' => $amount,
                'transactionId' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getWalletTransactions(string $accountNumber,string $fromDate,string $toDate,int $numberOfItems = 100): array
    {
        try {
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->post(
                    "{$this->baseUrl}/api/v1/wallet_transactions",
                    [
                        'accountNumber' => $accountNumber,
                        'fromDate'      => $fromDate,
                        'toDate'        => $toDate,
                        'numberOfItems' => (string) $numberOfItems,
                    ]
                );

            Log::info('Wallet Transactions Response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Wallet transaction fetch failed', [
                'accountNumber' => $accountNumber,
                'error'         => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function initiateWalletTier3Upgrade(array $data): array
    {
        try {

            $payload = [

                'accountNumber' => $data['accountNumber'],
                'bvn' => $data['bvn'],
                'nin' => $data['nin'],
                'accountName' => $data['accountName'],
                'channelType' => $data['channelType'],
                'phoneNumber' => $data['phoneNumber'],
                'tier' => $data['tier'],
                'email' => $data['email'],

                'userPhoto' => $this->imageToBase64($data['userPhoto']),

                'idType' => $data['idType'],
                'idNumber' => $data['idNumber'],
                'idIssueDate' => $data['idIssueDate'],
                'idExpiryDate' => $data['idExpiryDate'],

                'idCardFront' => $this->imageToBase64($data['idCardFront']),

                'idCardBack' => isset($data['idCardBack'])
                    ? $this->imageToBase64($data['idCardBack'])
                    : null,

                'houseNumber' => $data['houseNumber'],
                'streetName' => $data['streetName'],
                'state' => $data['state'],
                'city' => $data['city'],
                'localGovernment' => $data['localGovernment'],

                'approvalStatus' => $data['approvalStatus'] ?? null,
                'pep' => $data['pep'],

                'customerSignature' => $this->imageToBase64($data['customerSignature']),

                'utilityBill' => $this->imageToBase64($data['utilityBill']),

                'nearestLandmark' => $data['nearestLandmark'],
                'placeOfBirth' => $data['placeOfBirth'],

                'proofOfAddressVerification' => $this->imageToBase64(
                    $data['proofOfAddressVerification']
                ),
            ];

            $response = Http::withToken($this->token())
                ->acceptJson()
                ->post(
                    "{$this->baseUrl}/api/v1/wallet_upgrade",
                    $payload
                );

            Log::info('Wallet Tier 3 Upgrade Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Wallet Tier 3 Upgrade failed', [
                'accountNumber' => $data['accountNumber'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function upgradeWalletTier3(string $accountNumber,string $bvn,string $nin,UploadedFile $proofOfAddress): array
    {
        try {

            $response = Http::withToken($this->token())
                ->acceptJson()
                ->attach(
                    'proofOfAddressVerification',
                    fopen($proofOfAddress->getRealPath(), 'r'),
                    $proofOfAddress->getClientOriginalName()
                )
                ->post(
                    "{$this->baseUrl}/api/v1/walletUpgrade-tier3-multipart",
                    [
                        'accountNumber' => $accountNumber,
                        'bvn' => $bvn,
                        'nin' => $nin,
                    ]
                );

            Log::info('Wallet Tier 3 Upgrade Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Wallet Tier 3 Upgrade failed', [
                'accountNumber' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function imageToBase64(UploadedFile $image): string
    {
        return base64_encode(
            file_get_contents($image->getRealPath())
        );
    }
}