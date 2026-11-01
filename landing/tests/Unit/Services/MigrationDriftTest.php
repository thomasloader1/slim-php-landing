<?php

namespace Tests\Unit\Services;

use App\Services\MigrationService;
use App\Support\SqlSplitter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Tests\TestCase;

/**
 * db/install.sql es el único script de base de datos. Verifica que:
 *  - una base VACÍA y una base VIEJA (esquema anterior) terminen idénticas,
 *  - se pueda correr muchas veces sin errores ni cambios,
 *  - funcione como SQL plano (igual que importarlo en phpMyAdmin),
 *  - no pise datos (settings, permisos editados) y cree el usuario inicial solo si no hay usuarios.
 *
 * Usa bases descartables (*_test) que crea y borra.
 */
class MigrationDriftTest extends TestCase
{
    private const FRESH = 'project_landing_fresh_test';
    private const LEGACY = 'project_landing_legacy_test';
    private const RAW = 'project_landing_raw_test';

    private static bool $built = false;
    private static array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$built) {
            $this->buildDatabases();
            self::$built = true;
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$built) {
            foreach ([self::FRESH, self::LEGACY, self::RAW] as $db) {
                Capsule::connection('default')->statement("DROP DATABASE IF EXISTS `{$db}`");
            }
            self::$built = false;
        }
        parent::tearDownAfterClass();
    }

    private function buildDatabases(): void
    {
        $root = Capsule::connection('default');
        foreach ([self::FRESH, self::LEGACY, self::RAW] as $db) {
            $root->statement("DROP DATABASE IF EXISTS `{$db}`");
            $root->statement("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        // Base vieja: esquema anterior (ENUM de roles, sin productos/roles) + datos típicos
        $legacy = $this->conn(self::LEGACY);
        $this->runSql($legacy, file_get_contents(dirname(__DIR__, 2) . '/fixtures/legacy_site_schema.sql'));
        $this->runSql($legacy, file_get_contents(dirname(__DIR__, 2) . '/fixtures/legacy_master_schema.sql'));
        $legacy->table('users')->insert([
            ['name' => 'Dueño', 'email' => 'owner@legacy.test', 'password_hash' => 'x', 'role' => 'admin'],
            ['name' => 'Editora', 'email' => 'editor@legacy.test', 'password_hash' => 'x', 'role' => 'editor'],
        ]);
        $legacy->table('settings')->insert(['setting_key' => 'site_name', 'setting_value' => 'Negocio viejo']);

        $svc = new MigrationService();
        self::$logs['fresh'] = $svc->migrate($this->conn(self::FRESH), 'master');
        self::$logs['legacy'] = $svc->migrate($legacy, 'master');
    }

    /** Ejecuta SQL sentencia por sentencia, sin MigrationService (como phpMyAdmin / mysql CLI). */
    private function runSql(Connection $c, string $sql): void
    {
        foreach (SqlSplitter::split($sql) as $stmt) {
            $c->unprepared($stmt);
        }
    }

    private function installSql(): string
    {
        return file_get_contents(dirname(__DIR__, 3) . '/db/install.sql');
    }

    private function conn(string $db): Connection
    {
        $name = 'drift_' . $db;
        $config = Capsule::connection('default')->getConfig();
        $config['database'] = $db;
        self::$capsule->addConnection($config, $name);
        return self::$capsule->getConnection($name);
    }

    /** @return array<string,string> tabla => SHOW CREATE TABLE normalizado */
    private function schema(Connection $c): array
    {
        $out = [];
        foreach ($c->select('SHOW TABLES') as $row) {
            $table = (string) array_values((array) $row)[0];
            if ($table === 'schema_migrations') {
                continue;
            }
            $ddl = (array) $c->select("SHOW CREATE TABLE `{$table}`")[0];
            $out[$table] = preg_replace('/ AUTO_INCREMENT=\d+/', '', (string) array_values($ddl)[1]);
        }
        ksort($out);
        return $out;
    }

    private function rolePermissions(Connection $c): array
    {
        return $c->table('roles')
            ->leftJoin('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->orderBy('roles.slug')->orderBy('role_permissions.permission')
            ->get(['roles.slug', 'roles.is_system', 'role_permissions.permission'])
            ->map(fn ($r) => (array) $r)->all();
    }

    /** @test */
    public function empty_and_legacy_databases_end_with_identical_schema(): void
    {
        $fresh = $this->schema($this->conn(self::FRESH));
        $legacy = $this->schema($this->conn(self::LEGACY));

        $this->assertSame(array_keys($fresh), array_keys($legacy), 'Distintas tablas');
        foreach ($fresh as $table => $ddl) {
            $this->assertSame($ddl, $legacy[$table], "Esquema distinto en `{$table}`: falta un bloque \"si no existe, agregar\" en install.sql o viceversa");
        }
        $this->assertSame([], (new MigrationService())->missingTables($this->conn(self::FRESH), 'master'));
    }

    /** @test */
    public function both_paths_create_the_same_default_roles(): void
    {
        $fresh = $this->rolePermissions($this->conn(self::FRESH));
        $this->assertNotEmpty($fresh);
        $this->assertSame($fresh, $this->rolePermissions($this->conn(self::LEGACY)));
    }

    /** @test */
    public function works_as_plain_sql_and_can_run_many_times(): void
    {
        $raw = $this->conn(self::RAW);
        $this->runSql($raw, $this->installSql());
        $once = $this->schema($raw);
        $this->runSql($raw, $this->installSql()); // segunda vez: sin errores
        $this->runSql($raw, $this->installSql()); // tercera

        $this->assertSame($once, $this->schema($raw));
        $this->assertSame($this->schema($this->conn(self::FRESH)), $once);
        $this->assertSame(1, $raw->table('users')->count(), 'no duplica el usuario inicial');
    }

    /** @test */
    public function creates_initial_root_user_only_on_empty_database(): void
    {
        $user = $this->conn(self::FRESH)->table('users')->where('email', MigrationService::DEFAULT_USER_EMAIL)->first();
        $this->assertNotNull($user);
        $this->assertSame('root', $user->role);
        $this->assertTrue(password_verify('password', $user->password_hash));
        $this->assertStringContainsString('Usuario inicial creado', implode("\n", self::$logs['fresh']));

        $legacyEmails = $this->conn(self::LEGACY)->table('users')->orderBy('email')->pluck('role', 'email')->all();
        $this->assertSame(['editor@legacy.test' => 'editor', 'owner@legacy.test' => 'root'], $legacyEmails, 'base con usuarios: no agrega; su admin pasa a root');
    }

    /** @test */
    public function never_overwrites_settings_nor_restores_permissions_removed_by_root(): void
    {
        $legacy = $this->conn(self::LEGACY);
        $this->assertSame('Negocio viejo', $legacy->table('settings')->where('setting_key', 'site_name')->value('setting_value'));
        $this->assertNotNull($legacy->table('settings')->where('setting_key', 'menu_payment_methods')->value('setting_value'), 'agrega las claves que faltan');

        $editorId = $legacy->table('roles')->where('slug', 'editor')->value('id');
        $legacy->table('role_permissions')->where('role_id', $editorId)->where('permission', 'links')->delete();

        $log = (new MigrationService())->migrate($legacy, 'master');

        $this->assertFalse($legacy->table('role_permissions')->where('role_id', $editorId)->where('permission', 'links')->exists());
        $this->assertSame(1, count($log), 'segunda corrida: solo la línea del script');
    }

    /** @test */
    public function sites_created_from_the_manager_do_not_keep_the_generic_user(): void
    {
        $raw = $this->conn(self::RAW);
        $raw->table('users')->delete();

        (new MigrationService())->migrate($raw, 'site', true, false);

        $this->assertSame(0, $raw->table('users')->count());
    }
}
