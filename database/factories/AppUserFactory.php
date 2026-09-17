<?php

namespace Database\Factories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppUser>
 */
class AppUserFactory extends Factory
{
    protected $model = AppUser::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'phone' => fake()->unique()->numerify('080########'),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'password_hash' => Hash::make('password123'),
            'pin_hash' => Hash::make('1234'),
            'kyc_status' => 'VERIFIED',
            'tier' => 2,
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ];
    }
}
