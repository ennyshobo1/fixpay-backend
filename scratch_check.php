<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\AppUser::where('email', 'test.userA@fixpay.test')->first();

$controller = app(\App\Http\Controllers\User\AnalyticsController::class);
$request = \Illuminate\Http\Request::create('/api/analytics?period=7d', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });

$response = $controller->show($request);
echo $response->getContent();
