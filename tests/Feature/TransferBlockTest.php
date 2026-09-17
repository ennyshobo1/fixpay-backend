<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransferBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_transfer_to_bank()
    {
        $user = AppUser::factory()->create([
            'kyc_status' => 'UNVERIFIED',
            'status' => 'ACTIVE',
        ]);

        Wallet::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'balance_kobo' => 500000,
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/transfers/bank', [
            'amount_kobo' => 10000,
            'account_number' => '0123456789',
            'bank_code' => '058',
            'narration' => 'Test',
        ], [
            'X-Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Complete your KYC verification to initiate transfers.',
        ]);
    }

    public function test_unverified_user_cannot_transfer_to_wallet()
    {
        $user = AppUser::factory()->create([
            'kyc_status' => 'UNVERIFIED',
            'status' => 'ACTIVE',
        ]);

        Wallet::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'balance_kobo' => 500000,
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/transfers/wallet', [
            'amount_kobo' => 10000,
            'recipient_phone' => '08012345678',
            'narration' => 'Test',
        ], [
            'X-Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Complete your KYC verification to initiate transfers.',
        ]);
    }

    public function test_verified_user_can_transfer()
    {
        $user = AppUser::factory()->create([
            'kyc_status' => 'VERIFIED',
            'status' => 'ACTIVE',
        ]);

        Wallet::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'balance_kobo' => 500000,
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($user, ['*']);

        // Mock the transfer service to avoid hitting real APIs
        $this->mock(\App\Services\Transfer\TransferService::class, function ($mock) {
            $mock->shouldReceive('initiateBank')->andReturn(new \App\Models\Transfer([
                'transfer_reference' => 'ref_123',
                'status' => 'PENDING',
                'amount_kobo' => 10000,
                'fee_kobo' => 5000,
            ]));
        });

        $response = $this->postJson('/api/transfers/bank', [
            'amount_kobo' => 10000,
            'account_number' => '0123456789',
            'bank_code' => '058',
            'narration' => 'Test',
        ], [
            'X-Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        // Instead of 403, we should get 202 Accepted (as per controller)
        $response->assertStatus(202);
    }
}
