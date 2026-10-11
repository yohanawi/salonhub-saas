<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private static bool $schemaMigrated = false;

    /**
     * Most feature tests use DatabaseTransactions, which assumes the schema already exists.
     * Migrate once per process, before any test transaction starts, so the suite passes on a fresh database
     * regardless of test order.
     */
    protected function setUpTraits()
    {
        if (! self::$schemaMigrated) {
            Artisan::call('migrate', ['--force' => true]);
            self::$schemaMigrated = true;
        }

        return parent::setUpTraits();
    }
}
