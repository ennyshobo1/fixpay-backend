<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('Password123!');
        $pinHash = Hash::make('1234');

        // Initialize roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $consumerRole = Role::firstOrCreate(['name' => 'consumer', 'guard_name' => 'web']);

        // ── LiveTestTenant ───────────────────────────────────────────────────────
        $liveTenant = Tenant::updateOrCreate(
            ['slug' => 'live-test-tenant'],
            [
                'name'       => 'LiveTestTenant',
                'email'      => 'admin@livetesttenant.com',
                'phone'      => '08000000099',
                'status'     => 'ACTIVE',
                'plan'       => 'GROWTH',
                'kyb_status' => 'APPROVED',
                'activated_at' => now(),
                'branding'   => [
                    'appName'       => 'LiveTestTenant',
                    'primaryColor'  => '#6C5CE7',
                    'secondaryColor'=> '#00B894',
                    'accentColor'   => '#FDCB6E',
                    'logoUrl'       => null,
                    'faviconUrl'    => null,
                    'supportEmail'  => 'support@livetesttenant.com',
                    'supportPhone'  => '07000000099',
                ],
                'feature_flags' => [
                    'billPayments'         => true,
                    'directDebit'          => true,
                    'walletTransfers'      => true,
                    'internationalAirtime' => true,
                    'disputeManagement'    => true,
                    'nibssTransfers'       => true,
                ],
            ]
        );

        $portalTenant = Tenant::updateOrCreate(
            ['email' => 'seed.portal@fixpay.test'],
            [
                'name'       => 'Seed Portal Tenant',
                'slug'       => 'seed-portal-tenant',
                'phone'      => '08010000003',
                'status'     => 'SANDBOX',
                'plan'       => 'STARTER',
                'kyb_status' => 'SUBMITTED',
            ]
        );

        $seedUsers = [
            [
                'email' => 'seed.user@fixpay.test',
                'phone' => '08010000001',
                'first_name' => 'Seed',
                'last_name' => 'User',
                'tenant_id' => null,
                'role' => 'consumer',
                'kyc_status' => 'UNVERIFIED',
                'pin' => null,
                'balance' => 2500000,
            ],
            [
                'email'      => 'seed.admin@fixpay.test',
                'phone'      => '08010000002',
                'first_name' => 'Seed',
                'last_name'  => 'Admin',
                'tenant_id'  => $liveTenant->id, // Admin owns / manages LiveTestTenant
                'role'       => 'admin',
                'kyc_status' => 'UNVERIFIED',
                'pin'        => null,
                'balance'    => 2500000,
            ],
            [
                'email' => 'seed.portal@fixpay.test',
                'phone' => '08010000003',
                'first_name' => 'Seed',
                'last_name' => 'Portal',
                'tenant_id' => $portalTenant->id,
                'role' => 'consumer',
                'kyc_status' => 'UNVERIFIED',
                'pin' => null,
                'balance' => 2500000,
            ],
            [
                'email' => 'test.userA@fixpay.test',
                'phone' => '08099999991',
                'first_name' => 'TestA',
                'last_name' => 'User',
                'tenant_id' => null,
                'role' => 'consumer',
                'kyc_status' => 'VERIFIED',
                'pin' => $pinHash,
                'balance' => 10000000, // NGN 100,000
            ],
            [
                'email' => 'test.userB@fixpay.test',
                'phone' => '08099999992',
                'first_name' => 'TestB',
                'last_name' => 'User',
                'tenant_id' => null,
                'role' => 'consumer',
                'kyc_status' => 'VERIFIED',
                'pin' => $pinHash,
                'balance' => 1000000, // NGN 10,000
            ],
        ];

        foreach ($seedUsers as $seedUser) {
            $user = AppUser::updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'phone' => $seedUser['phone'],
                    'first_name' => $seedUser['first_name'],
                    'last_name' => $seedUser['last_name'],
                    'tenant_id' => $seedUser['tenant_id'],
                    'password_hash' => $passwordHash,
                    'pin_hash' => $seedUser['pin'],
                    'kyc_status' => $seedUser['kyc_status'],
                    'tier' => $seedUser['kyc_status'] === 'VERIFIED' ? 2 : 1,
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ]
            );

            // Assign role
            $user->assignRole($seedUser['role']);

            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'tenant_id' => $user->tenant_id,
                    'balance_kobo' => $seedUser['balance'],
                    'ledger_balance_kobo' => $seedUser['balance'],
                    'currency' => 'NGN',
                    'status' => 'ACTIVE',
                    'virtual_account_number' => null,
                    'virtual_account_bank' => null,
                    'virtual_account_bank_code' => null,
                    'virtual_account_reference' => null,
                ]
            );
        }
    }
}
