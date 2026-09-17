<?php

use App\Console\Commands\TimeoutStalePaymentsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every 5 minutes to catch stale payments
Schedule::command(TimeoutStalePaymentsCommand::class)->everyFiveMinutes();

