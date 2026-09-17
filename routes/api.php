<?php

use App\Http\Controllers\Insurance\InsuranceController;
use App\Http\Controllers\Admin\PaymentRailAdminController;
use App\Http\Controllers\Admin\TenantAdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PinController;
use App\Http\Controllers\Compliance\ComplianceController;
use App\Http\Controllers\Compliance\DisputeController;
use App\Http\Controllers\Mandate\MandateController;
use App\Http\Controllers\Payment\VtpassPaymentController;
use App\Http\Controllers\Portal\ApiKeyController;
use App\Http\Controllers\Portal\IpWhitelistController;
use App\Http\Controllers\Portal\KybController;
use App\Http\Controllers\Portal\PortalRegistrationController;
use App\Http\Controllers\Portal\SettlementController;
use App\Http\Controllers\Portal\TenantPortalController;
use App\Http\Controllers\Portal\WebhookController;
use App\Http\Controllers\Tenant\TenantConfigController;
use App\Http\Controllers\Transfer\PaystackWebhookController;
use App\Http\Controllers\Transfer\TransferController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Wallet\FundWalletController;
use App\Http\Controllers\Payment\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// ── Public routes ─────────────────────────────────────────────────────────

// Tenant branding config — public, rate-limited, returns only safe branding data.
// Unknown slugs return an opaque 404. No UUIDs or financial data exposed.

Route::get('/health', function () {
    dd('health reached, and running');
});

Route::middleware('throttle:30,1')->get('tenant/config', [TenantConfigController::class, 'show']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
});

Route::post('portal/register', [PortalRegistrationController::class, 'register']);

// Paystack webhook (public, verified by signature)
Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle']);

// ── Authenticated consumer routes ─────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);

    // PIN
    Route::prefix('auth/pin')->group(function () {
        Route::post('set', [PinController::class, 'set']);
        Route::post('verify', [PinController::class, 'verify']);
        Route::put('change', [PinController::class, 'change']);
    });

    // User profile
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
    });

    Route::prefix('insurance')->group(function () {
        Route::post('buy', [InsuranceController::class, 'getInsurance']);
    });

    // KYC
    Route::prefix('kyc')->group(function () {
        Route::post('bvn', [KycController::class, 'verifyBvn']);
        Route::post('nin', [KycController::class, 'verifyNin']);
        Route::get('status', [KycController::class, 'status']);
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::post('/history', [WalletController::class, 'walletTransactions']);
        Route::post('/initiate-upgrade', [WalletController::class, 'initiateUpgrade']);
        Route::post('/upgrade-tier3', [WalletController::class, 'upgradeTier3']);
        Route::post('/create',[WalletController::class,'create']);
        Route::post('/fund',[FundWalletController::class,'initialize'])->middleware('idempotent');    
        Route::get('/', [WalletController::class, 'show']);
        Route::get('transactions', [WalletController::class, 'transactions']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/webhook', [PaymentWebhookController::class,'handle'])->middleware('idempotent');
        Route::get('vtpass/services', [VtpassPaymentController::class, 'services']);
        Route::get('vtpass/variations', [VtpassPaymentController::class, 'variations']);
        Route::post('verify', [VtpassPaymentController::class, 'verify']);
        Route::post('vtpass', [VtpassPaymentController::class, 'pay'])->middleware('idempotent');
        Route::get('vtpass/{reference}', [VtpassPaymentController::class, 'status']);
    });

    // Transfers
    Route::prefix('transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index']);
        Route::post('bank', [TransferController::class, 'toBank'])->middleware('idempotent');
        Route::post('wallet', [TransferController::class, 'toWallet'])->middleware('idempotent');
        Route::get('{reference}', [TransferController::class, 'status']);
    });

    // Mandates
    Route::prefix('mandates')->group(function () {
        Route::get('/', [MandateController::class, 'index']);
        Route::post('/', [MandateController::class, 'create']);
        Route::get('{id}', [MandateController::class, 'show']);
        Route::delete('{id}', [MandateController::class, 'cancel']);
    });

    // Disputes
    Route::prefix('disputes')->group(function () {
        Route::get('/', [DisputeController::class, 'index']);
        Route::post('/', [DisputeController::class, 'create']);
        Route::get('{id}', [DisputeController::class, 'show']);
    });
});

// ── Portal routes (API key auth) ──────────────────────────────────────────
Route::prefix('portal')->middleware(['api.key', 'tenant', 'ip.whitelist'])->group(function () {
    Route::prefix('kyb')->group(function () {
        Route::get('/', [KybController::class, 'show']);
        Route::post('/', [KybController::class, 'submit']);
    });

    Route::prefix('api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/', [ApiKeyController::class, 'create']);
        Route::delete('{id}', [ApiKeyController::class, 'revoke']);
    });

    Route::prefix('webhooks')->group(function () {
        Route::get('/', [WebhookController::class, 'index']);
        Route::post('/', [WebhookController::class, 'create']);
        Route::delete('{id}', [WebhookController::class, 'delete']);
    });

    Route::prefix('ip-whitelist')->group(function () {
        Route::get('/', [IpWhitelistController::class, 'index']);
        Route::post('/', [IpWhitelistController::class, 'create']);
        Route::delete('{id}', [IpWhitelistController::class, 'delete']);
    });

    Route::prefix('settlement')->group(function () {
        Route::get('/', [SettlementController::class, 'show']);
        Route::post('/', [SettlementController::class, 'upsert']);
    });

    Route::get('profile', [TenantPortalController::class, 'profile']);
    Route::put('profile', [TenantPortalController::class, 'updateProfile']);
    Route::post('go-live', [TenantPortalController::class, 'requestGoLive']);
});

// ── Admin routes (Sanctum + role guard) ──────────────────────────────────
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantAdminController::class, 'index']);
        Route::get('{id}', [TenantAdminController::class, 'show']);
        Route::put('{id}/status', [TenantAdminController::class, 'updateStatus']);
        Route::put('{id}/kyb', [TenantAdminController::class, 'reviewKyb']);
    });

    Route::prefix('payment-rails')->group(function () {
        Route::get('/', [PaymentRailAdminController::class, 'index']);
        Route::post('/', [PaymentRailAdminController::class, 'create']);
        Route::put('{id}', [PaymentRailAdminController::class, 'update']);
        Route::post('{id}/fee-schedules', [PaymentRailAdminController::class, 'addFeeSchedule']);
    });

    Route::prefix('disputes')->group(function () {
        Route::get('/', [DisputeController::class, 'adminIndex']);
        Route::put('{id}', [DisputeController::class, 'resolve']);
    });

    Route::prefix('compliance')->group(function () {
        Route::get('users', [ComplianceController::class, 'userList']);
        Route::post('screen/{userId}', [ComplianceController::class, 'screenUser']);
    });
});
