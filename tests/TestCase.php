<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $pdo = DB::connection()->getPdo();
                if (method_exists($pdo, 'sqliteCreateFunction')) {
                    $pdo->sqliteCreateFunction('gen_random_uuid', function () {
                        return (string) Str::uuid();
                    });
                }
            }
        } catch (\Throwable $e) {
            // connection might not be established yet or database doesn't exist
        }
    }
}
