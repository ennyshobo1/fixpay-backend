<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Transfer;
use App\Models\VtpassPayment;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = AppUser::factory()->create();
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id,
            'balance_kobo' => 100000,
            'ledger_balance_kobo' => 100000,
            'currency' => 'NGN',
            'status' => 'ACTIVE',
            'virtual_account_number' => '1234567890',
            'virtual_account_bank' => 'Providus Bank',
            'virtual_account_bank_code' => '101',
            'virtual_account_reference' => 'ref-123',
        ]);
    }

    public function test_missing_idempotency_header_returns_400(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/vtpass', [
                'service_id' => 'ikeja-electric',
                'amount_kobo' => 5000,
                'phone' => '08012345678',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'X-Idempotency-Key header is required.');
    }

    public function test_invalid_uuid_idempotency_header_returns_400(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Idempotency-Key', 'invalid-uuid')
            ->postJson('/api/payments/vtpass', [
                'service_id' => 'ikeja-electric',
                'amount_kobo' => 5000,
                'phone' => '08012345678',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'X-Idempotency-Key must be a valid UUID.');
    }

    public function test_concurrent_request_returns_409(): void
    {
        $key = Str::uuid()->toString();
        Cache::add("idempotency_lock:{$key}", true, 30);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Idempotency-Key', $key)
            ->postJson('/api/payments/vtpass', [
                'service_id' => 'ikeja-electric',
                'amount_kobo' => 5000,
                'phone' => '08012345678',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Concurrent request in progress.');
    }

    public function test_returns_cached_completed_vtpass_payment(): void
    {
        $key = Str::uuid()->toString();

        $payment = VtpassPayment::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'tenant_id' => $this->user->tenant_id,
            'payment_reference' => 'FP-TEST-123',
            'idempotency_key' => $key,
            'service_id' => 'ikeja-electric',
            'amount_kobo' => 5000,
            'fee_kobo' => 100,
            'phone' => '08012345678',
            'payment_status' => 'COMPLETED',
            'token' => 'TOKEN123',
            'units' => '12.4',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Idempotency-Key', $key)
            ->postJson('/api/payments/vtpass', [
                'service_id' => 'ikeja-electric',
                'amount_kobo' => 5000,
                'phone' => '08012345678',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'payment_reference' => 'FP-TEST-123',
                'status' => 'COMPLETED',
                'token' => 'TOKEN123',
                'units' => '12.4',
                'amount_kobo' => 5000,
                'fee_kobo' => 100,
            ]);
    }
}
