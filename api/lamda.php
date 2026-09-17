<?php

//use Illuminate\Foundation\Application;
//use Illuminate\Http\Request;

//define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
//if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
//    require $maintenance;
//}

// Register the Composer autoloader...
//require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
//$app = require_once __DIR__.'/../bootstrap/app.php';

//$app->handleRequest(Request::capture());

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {

    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    $app->handleRequest(Request::capture());

} catch (Throwable $e) {

    echo '<pre>';
    echo $e->getMessage() . PHP_EOL . PHP_EOL;
    echo $e->getTraceAsString();
    echo '</pre>';

}