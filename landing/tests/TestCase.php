<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected static $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        // Bootstrap Eloquent Capsule for tests
        $dbConfig = [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'mysql-server',
            'database'  => $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'project_landing_test',
            'username'  => $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'main_user',
            'password'  => $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? 'password',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];

        // Nunca correr tests contra una DB que no sea de test (borran/insertan datos)
        if (!str_ends_with($dbConfig['database'], '_test')) {
            throw new \RuntimeException("Tests abortados: DB '{$dbConfig['database']}' no termina en _test");
        }

        if (!self::$capsule) {
            self::$capsule = new Capsule;
            self::$capsule->addConnection($dbConfig);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }
        // Ensure default connection is reset for each test
        $this->resetDefaultConnection();
    }

    protected function tearDown(): void
    {
        // Clean up tenant connections
        if (self::$capsule) {
            $mgr = self::$capsule->getDatabaseManager();
            $connections = $mgr->getConnections();
            foreach ($connections as $name => $conn) {
                if ($name !== 'default') {
                    $mgr->purge($name); // DatabaseManager no tiene forgetConnection()
                }
            }
        }
        $this->resetDefaultConnection();
        parent::tearDown();
    }

    private function resetDefaultConnection(): void
    {
        if (self::$capsule) {
            $mgr = self::$capsule->getDatabaseManager();
            $mgr->setDefaultConnection('default');
        }
    }

    // Helper to get DatabaseManager for direct connection management
    protected static function getDatabaseManager()
    {
        if (self::$capsule) {
            return self::$capsule->getDatabaseManager();
        }
        return null;
    }
}
