<?php

namespace Tests\Unit;

use PDO;
use Tests\TestCase;

class DatabaseMysqlSslConfigTest extends TestCase
{
    public function test_mysql_ssl_is_disabled_when_env_is_empty_or_false(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is not loaded.');
        }

        foreach ([null, '', 'false'] as $value) {
            $config = $this->loadDatabaseConfig([
                'MYSQL_ATTR_SSL_CA' => $value,
                'MYSQL_ATTR_SSL_CA_PATH' => null,
            ]);

            $this->assertSame([], $config['connections']['mysql']['options']);
        }
    }

    public function test_mysql_ssl_uses_default_ca_bundle_when_true(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is not loaded.');
        }

        $config = $this->loadDatabaseConfig([
            'MYSQL_ATTR_SSL_CA' => 'true',
            'MYSQL_ATTR_SSL_CA_PATH' => null,
        ]);

        $this->assertSame(
            [PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt'],
            $config['connections']['mysql']['options'],
        );
    }

    public function test_mysql_ssl_uses_custom_ca_path_override_when_true(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is not loaded.');
        }

        $config = $this->loadDatabaseConfig([
            'MYSQL_ATTR_SSL_CA' => 'true',
            'MYSQL_ATTR_SSL_CA_PATH' => '/custom/certs/enzonic-ca.pem',
        ]);

        $this->assertSame(
            [PDO::MYSQL_ATTR_SSL_CA => '/custom/certs/enzonic-ca.pem'],
            $config['connections']['mysql']['options'],
        );
    }

    public function test_mysql_ssl_uses_explicit_string_path(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is not loaded.');
        }

        $config = $this->loadDatabaseConfig([
            'MYSQL_ATTR_SSL_CA' => '/etc/ssl/certs/custom-ca.crt',
            'MYSQL_ATTR_SSL_CA_PATH' => null,
        ]);

        $this->assertSame(
            [PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/custom-ca.crt'],
            $config['connections']['mysql']['options'],
        );
    }

    public function test_local_mysql_configuration_remains_compatible(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is not loaded.');
        }

        $config = $this->loadDatabaseConfig([
            'MYSQL_ATTR_SSL_CA' => null,
            'MYSQL_ATTR_SSL_CA_PATH' => null,
        ]);

        $this->assertSame('mysql', $config['connections']['mysql']['driver']);
        $this->assertArrayNotHasKey(PDO::MYSQL_ATTR_SSL_CA, $config['connections']['mysql']['options']);
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, mixed>
     */
    private function loadDatabaseConfig(array $overrides): array
    {
        $previous = [];

        foreach ($overrides as $key => $value) {
            $previous[$key] = [
                'getenv' => getenv($key),
                'env' => array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
                'has_env' => array_key_exists($key, $_ENV),
                'server' => array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
                'has_server' => array_key_exists($key, $_SERVER),
            ];

            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        try {
            return require config_path('database.php');
        } finally {
            foreach ($previous as $key => $state) {
                if ($state['getenv'] === false) {
                    putenv($key);
                } else {
                    putenv("{$key}={$state['getenv']}");
                }

                if ($state['has_env']) {
                    $_ENV[$key] = $state['env'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($state['has_server']) {
                    $_SERVER[$key] = $state['server'];
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}
