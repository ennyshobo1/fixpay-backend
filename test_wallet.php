<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application kernel so Auth and other services are fully ready
$response = $kernel->handle(Illuminate\Http\Request::capture());

$user = App\Models\AppUser::where('email', 'test.userA@fixpay.test')->first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

Auth::login($user);

$request = Illuminate\Http\Request::create('/api/wallet', 'GET');
$response = $kernel->handle($request);
echo $response->getContent() . "\n";
