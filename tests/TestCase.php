<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private static bool $testingDatabaseVerified = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak boleh memanggil layanan luar sungguhan.
        Http::preventStrayRequests();
    }

    protected function setUpTraits()
    {
        $this->ensureSafeTestingDatabase();

        return parent::setUpTraits();
    }

    /**
     * Pastikan test hanya berjalan pada environment testing dan database pakelagi_test.
     */
    protected function ensureSafeTestingDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(sprintf(
                'Proteksi testing aktif: APP_ENV harus "testing", tetapi terdeteksi "%s".',
                app()->environment()
            ));
        }

        $connection = (string) config('database.default');
        if ($connection !== 'mysql') {
            throw new RuntimeException(sprintf(
                'Proteksi testing aktif: koneksi database harus "mysql", tetapi terdeteksi "%s".',
                $connection
            ));
        }

        $configuredDatabase = (string) config("database.connections.{$connection}.database");
        if ($configuredDatabase !== 'pakelagi_test') {
            throw new RuntimeException(sprintf(
                'Proteksi testing aktif: database testing harus "pakelagi_test", tetapi terdeteksi "%s". Pengujian dibatalkan untuk melindungi database development/production.',
                $configuredDatabase
            ));
        }

        if (! self::$testingDatabaseVerified) {
            $activeDatabase = (string) (DB::connection($connection)->selectOne('SELECT DATABASE() AS db')->db ?? '');

            if ($activeDatabase !== 'pakelagi_test') {
                throw new RuntimeException(sprintf(
                    'Proteksi testing aktif: koneksi MySQL aktif mengarah ke "%s", bukan "pakelagi_test". Pengujian dibatalkan.',
                    $activeDatabase
                ));
            }

            self::$testingDatabaseVerified = true;
        }
    }
}
