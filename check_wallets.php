<?php foreach (App\Models\AppUser::all() as $u) echo $u->email . " : " . ($u->wallet ? $u->wallet->balance_kobo : "No Wallet") . PHP_EOL;
