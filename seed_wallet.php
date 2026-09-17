<?php
$u = App\Models\AppUser::where("email", "admin@fixpay.com")->first();
if ($u && !$u->wallet) {
    App\Models\Wallet::create([
        "id" => (string) Illuminate\Support\Str::uuid(),
        "user_id" => $u->id,
        "balance_kobo" => 5000000,
        "ledger_balance_kobo" => 5000000,
        "currency" => "NGN",
        "status" => "ACTIVE"
    ]);
    echo "Wallet created for admin@fixpay.com";
} else {
    echo "User not found or wallet already exists";
}

