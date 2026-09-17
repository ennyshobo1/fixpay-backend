<?php

namespace App\Providers;

use App\Contracts\Kyc\AmlProviderInterface;
use App\Contracts\Kyc\KycProviderInterface;
use App\Services\Kyc\MockAmlAdapter;
use App\Services\Kyc\MockKycAdapter;
use App\Services\Kyc\PremblyKycAdapter;
use App\Services\Payment\PaymentRailService;
use App\Services\Payment\VtpassService;
use App\Services\Providus\ProvidusVirtualAccountAdapter;
use App\Services\Wallet\DigitalBankingService;
use App\Services\Transfer\TransferService;
use App\Services\Wallet\WalletService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── KYC Provider ────────────────────────────────────────────────────
        $this->app->bind(KycProviderInterface::class, function () {
            return match (config('services.kyc.provider', 'mock')) {
                'prembly' => new PremblyKycAdapter(
                    http: new Client(['timeout' => 30]),
                    apiKey: config('services.prembly.api_key', ''),
                    baseUrl: config('services.prembly.base_url', ''),
                ),
                default => new MockKycAdapter(),
            };
        });

        // ── AML Provider ─────────────────────────────────────────────────────
        $this->app->bind(AmlProviderInterface::class, function () {
            return match (config('services.aml.provider', 'mock')) {
                default => new MockAmlAdapter(),
            };
        });

        // ── Providus Virtual Account ─────────────────────────────────────────
        $this->app->singleton(ProvidusVirtualAccountAdapter::class, function () {
            return new ProvidusVirtualAccountAdapter(
                http: new Client(['timeout' => 30]),
                clientId: config('services.providus.client_id', ''),
                clientSecret: config('services.providus.client_secret', ''),
                baseUrl: config('services.providus.base_url', ''),
            );
        });

        // ── Wallet Service ────────────────────────────────────────────────────
        $this->app->singleton(WalletService::class, function ($app) {
            return new WalletService(
                virtualAccount: $app->make(ProvidusVirtualAccountAdapter::class),
                digitalBankingService: $app->make(DigitalBankingService::class),
            );
        });

        // ── VTPass Service ────────────────────────────────────────────────────
        $this->app->singleton(VtpassService::class, function ($app) {
            return new VtpassService(
                // verify:false disables SSL cert check on Windows dev — sandbox only, remove for production
                http: new Client(['timeout' => 60, 'verify' => false]),
                walletService: $app->make(WalletService::class),
                railService: $app->make(PaymentRailService::class),
                apiKey: config('services.vtpass.api_key', ''),
                secretKey: config('services.vtpass.secret_key', ''),
                publicKey: config('services.vtpass.public_key', ''),
                baseUrl: config('services.vtpass.base_url', ''),
            );
        });

        // ── Transfer Service ─────────────────────────────────────────────────
        $this->app->singleton(TransferService::class, function ($app) {
            return new TransferService(
                http: new Client(['timeout' => 60]),
                walletService: $app->make(WalletService::class),
                railService: $app->make(PaymentRailService::class),
                paystackSecretKey: config('services.paystack.secret_key', ''),
                paystackBaseUrl: config('services.paystack.base_url', ''),
            );
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
    }
}
