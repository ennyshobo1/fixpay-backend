<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Dispute;
use App\Models\NibssMandate;
use App\Models\Transfer;
use App\Models\VtpassPayment;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $userA;
    private AppUser $userB;
    private AppUser $adminUser;
    private Wallet $walletA;
    private Wallet $walletB;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $consumerRole = Role::firstOrCreate(['name' => 'consumer', 'guard_name' => 'web']);

        // Create User A
        $this->userA = AppUser::create([
            'email' => 'userA@test.com',
            'phone' => '08099999991',
            'first_name' => 'User',
            'last_name' => 'A',
            'password_hash' => Hash::make('Password123!'),
            'kyc_status' => 'VERIFIED',
            'tier' => 2,
            'status' => 'ACTIVE',
        ]);
        $this->userA->assignRole($consumerRole);

        $this->walletA = Wallet::create([
            'user_id' => $this->userA->id,
            'balance_kobo' => 500000,
            'ledger_balance_kobo' => 500000,
            'currency' => 'NGN',
            'status' => 'ACTIVE',
        ]);

        // Create User B
        $this->userB = AppUser::create([
            'email' => 'userB@test.com',
            'phone' => '08099999992',
            'first_name' => 'User',
            'last_name' => 'B',
            'password_hash' => Hash::make('Password123!'),
            'kyc_status' => 'VERIFIED',
            'tier' => 2,
            'status' => 'ACTIVE',
        ]);
        $this->userB->assignRole($consumerRole);

        $this->walletB = Wallet::create([
            'user_id' => $this->userB->id,
            'balance_kobo' => 100000,
            'ledger_balance_kobo' => 100000,
            'currency' => 'NGN',
            'status' => 'ACTIVE',
        ]);

        // Create Admin
        $this->adminUser = AppUser::create([
            'email' => 'admin@test.com',
            'phone' => '08099999993',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'password_hash' => Hash::make('Password123!'),
            'kyc_status' => 'VERIFIED',
            'tier' => 2,
            'status' => 'ACTIVE',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    /** Test consumer users cannot access admin endpoints. */
    public function test_consumer_user_cannot_access_admin_endpoints(): void
    {
        $response = $this->actingAs($this->userA)
            ->getJson('/api/admin/tenants');

        $response->assertStatus(403);
    }

    /** Test admin users can access admin endpoints. */
    public function test_admin_user_can_access_admin_endpoints(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/tenants');

        $response->assertStatus(200);
    }

    /** Test User B cannot query User A's Vtpass status. */
    public function test_user_cannot_access_other_users_vtpass_status(): void
    {
        $payment = VtpassPayment::create([
            'user_id' => $this->userA->id,
            'wallet_id' => $this->walletA->id,
            'payment_reference' => 'REF-A-123',
            'service_id' => 'mtn-airtime',
            'amount_kobo' => 10000,
            'phone' => '08099999991',
            'payment_status' => 'COMPLETED',
        ]);

        // User A can access it
        $this->actingAs($this->userA)
            ->getJson("/api/payments/vtpass/{$payment->payment_reference}")
            ->assertStatus(200);

        // User B cannot access it
        $this->actingAs($this->userB)
            ->getJson("/api/payments/vtpass/{$payment->payment_reference}")
            ->assertStatus(404);
    }

    /** Test User B cannot query User A's Transfer status. */
    public function test_user_cannot_access_other_users_transfer_status(): void
    {
        $transfer = Transfer::create([
            'user_id' => $this->userA->id,
            'wallet_id' => $this->walletA->id,
            'transfer_reference' => 'TX-A-123',
            'transfer_type' => 'BANK',
            'amount_kobo' => 20000,
            'status' => 'COMPLETED',
            'account_number' => '1234567890',
            'bank_code' => '058',
        ]);

        // User A can access it
        $this->actingAs($this->userA)
            ->getJson("/api/transfers/{$transfer->transfer_reference}")
            ->assertStatus(200);

        // User B cannot access it
        $this->actingAs($this->userB)
            ->getJson("/api/transfers/{$transfer->transfer_reference}")
            ->assertStatus(404);
    }

    /** Test User B cannot link a dispute to User A's VtpassPayment. */
    public function test_user_cannot_create_dispute_for_other_users_payment(): void
    {
        $payment = VtpassPayment::create([
            'user_id' => $this->userA->id,
            'wallet_id' => $this->walletA->id,
            'payment_reference' => 'REF-A-123',
            'service_id' => 'mtn-airtime',
            'amount_kobo' => 10000,
            'phone' => '08099999991',
            'payment_status' => 'COMPLETED',
        ]);

        // User B tries to dispute User A's payment
        $response = $this->actingAs($this->userB)
            ->postJson('/api/disputes', [
                'related_payment_id' => $payment->id,
                'related_payment_type' => 'VTPASS',
                'category' => 'WRONG_AMOUNT',
                'reason' => 'Fraudulent charge',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Invalid or unauthorized payment ID.');
    }
}
