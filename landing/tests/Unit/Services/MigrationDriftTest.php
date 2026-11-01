<?php

namespace Tests\Unit\Services;

use App\Services\MigrationService;
use App\Support\SqlSplitter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Tests\TestCase;

/**
 * Garantiza que una base NUEVA (db/schema/*.sql) y una base VIEJA actualizada
 * con db/upgrades/** terminen con exactamente el mismo esquema y datos base.
 * Si falla: agregaste un cambio en un lado y no en el otro.
 *
 * Usa dos bases descartables (*_test) que crea y borra.
 */
class MigrationDriftTest extends TestCase
{
    private const FRESH = 'project_landing_fresh_test';
    private const LEGACY = 'project_landing_legacy_test';

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
            foreach ([self::FRESH, self::LEGACY] as $db) {
                Capsule::connection('default')->statement("DROP DATABASE IF EXISTS `{$db}`");
            }
            self::$built = false;
        }
        parent::tearDownAfterClass();
    }

    private function buildDatabases(): void
    {
        $root = Capsule::connection('default');
        foreach ([self::FRESH, self::LEGACY] as $db) {
            $root->statement("DROP DATABASE IF EXISTS `{$db}`");
            $root->statement("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        // Base vieja: esquema anterior a esta refactorización + datos típicos
        $legacy = $this->conn(self::LEGACY);
        foreach (['legacy_site_schema.sql', 'legacy_master_schema.sql'] as $fixture) {
            foreach (SqlSplitter::split(file_get_contents(dirname(__DIR__, 2) . '/fixtures/' . $fixture)) as $stmt) {
                $legacy->unprepared($stmt);
            }
        }
        $legacy->table('users')->insert([
            ['name' => 'Dueño', 'email' => 'owner@legacy.test', 'password_hash' => 'x', 'role' => 'admin'],
            ['name' => 'Editora', 'email' => 'editor@legacy.test', 'password_hash' => 'x', 'role' => 'editor'],
        ]);

        $svc = new MigrationService();
        self::$logs['fresh'] = $svc->migrate($this->conn(self::FRESH), 'master', true);
        self::$logs['legacy'] = $svc->migrate($legacy, 'master', true);
    }

    /** Conexión a una de las bases descartables (mismo servidor que la de test). */
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
    public function fresh_install_and_upgraded_legacy_db_have_identical_schema(): void
    {
        $fresh = $this->schema($this->conn(self::FRESH));
        $legacy = $this->schema($this->conn(self::LEGACY));

        $this->assertSame(array_keys($fresh), array_keys($legacy), 'Distintas tablas');
        foreach ($fresh as $table => $ddl) {
            $this->assertSame($ddl, $legacy[$table], "Esquema distinto en `{$table}`: falta reflejar un upgrade en db/schema/ o viceversa");
        }
    }

    /** @test */
    public function both_paths_create_the_same_default_roles(): void
    {
        $fresh = $this->rolePermissions($this->conn(self::FRESH));
        $this->assertNotEmpty($fresh);
        $this->assertSame($fresh, $this->rolePermissions($this->conn(self::LEGACY)));
    }

    /** @test */
    public function fresh_install_runs_schema_and_marks_upgrades_without_running_them(): void
    {
        $this->assertStringContainsString('base nueva creada', implode("\n", self::$logs['fresh']));
        $this->assertStringNotContainsString('upgrades/site/', implode("\n", self::$logs['fresh']));

        foreach ((new MigrationService())->status($this->conn(self::FRESH), 'master') as $row) {
            $this->assertSame('applied', $row['status'], $row['file']);
        }
    }

    /** @test */
    public function legacy_admins_become_root_in_master_and_editors_stay(): void
    {
        $roles = $this->conn(self::LEGACY)->table('users')->orderBy('email')->pluck('role', 'email')->all();
        $this->assertSame(['editor@legacy.test' => 'editor', 'owner@legacy.test' => 'root'], $roles);
    }

    /** @test */
    public function migrating_again_changes_nothing_and_does_not_rerun_upgrades(): void
    {
        $legacy = $this->conn(self::LEGACY);
        $before = $this->schema($legacy);

        $log = (new MigrationService())->migrate($legacy, 'master');

        $this->assertSame(['Esquema site: al día', 'Esquema master: al día'], $log);
        $this->assertSame($before, $this->schema($legacy));
    }

    /** @test */
    public function seed_never_overwrites_existing_settings_nor_creates_users(): void
    {
        $fresh = $this->conn(self::FRESH);
        $fresh->table('settings')->where('setting_key', 'site_name')->update(['setting_value' => 'Mi negocio']);
        $fresh->table('settings')->where('setting_key', 'landing_subtitle')->delete();

        (new MigrationService())->migrate($fresh, 'site', true);

        $this->assertSame('Mi negocio', $fresh->table('settings')->where('setting_key', 'site_name')->value('setting_value'));
        $this->assertNotNull($fresh->table('settings')->where('setting_key', 'landing_subtitle')->value('setting_value'), 'el seed repone claves faltantes');
        $this->assertSame(0, $fresh->table('users')->count());
    }
}
