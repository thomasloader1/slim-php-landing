<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Prepara la DB propia de un sitio cliente.
 *
 *  - createDatabase(): crea DB + usuario MySQL desde la conexión master. Solo funciona
 *    si el usuario master tiene CREATE/CREATE USER/GRANT (Docker local, VPS).
 *    En cPanel no: ahí se crean desde cPanel o vía SSH (ServerProvisioner).
 *  - initialize(): aplica esquema + settings base y crea el admin del sitio.
 *    Funciona en cPanel porque la DB del cliente está en el mismo MySQL (localhost).
 */
class TenantProvisioner
{
    public const IDENTIFIER = '/^[A-Za-z0-9_]{1,64}$/D';

    private SiteConnectionService $connections;
    private MigrationService $migrations;

    public function __construct(?SiteConnectionService $connections = null, ?MigrationService $migrations = null)
    {
        $this->connections = $connections ?? new SiteConnectionService(new EncryptionService());
        $this->migrations = $migrations ?? new MigrationService();
    }

    /**
     * Aplica el esquema pendiente a la DB de un sitio ya inicializado.
     * Sin seed: nunca toca settings, usuarios ni contenido del cliente.
     *
     * @return string[] log
     */
    public function migrateSite(Site $site): array
    {
        $conn = Capsule::connection($this->connections->connect($site));
        $conn->getPdo(); // falla rápido si la DB no responde
        return $this->migrations->migrate($conn, 'site');
    }

    /**
     * Migra todos los sitios inicializados. Un sitio con error no frena al resto.
     *
     * @return array{log: string[], ok: int, failed: int}
     */
    public function migrateAllSites(): array
    {
        $log = [];
        $ok = 0;
        $failed = 0;
        foreach (Site::whereNotNull('initialized_at')->orderBy('id')->get() as $site) {
            try {
                foreach ($this->migrateSite($site) as $line) {
                    $log[] = "[{$site->domain}] {$line}";
                }
                $ok++;
            } catch (\Throwable $e) {
                $failed++;
                $log[] = "[{$site->domain}] ERROR: " . $e->getMessage();
                error_log("[TenantProvisioner] migrate site {$site->id}: " . $e->getMessage());
            }
        }
        return ['log' => $log, 'ok' => $ok, 'failed' => $failed];
    }

    /** @return string[] log */
    public function createDatabase(Site $site, string $plainPassword): array
    {
        foreach (['db_name' => $site->db_name, 'db_user' => $site->db_user] as $field => $value) {
            if (!preg_match(self::IDENTIFIER, (string) $value)) {
                throw new \InvalidArgumentException("{$field} inválido: solo letras, números y _ (máx. 64).");
            }
            if (SiteValidator::isReserved((string) $value)) {
                throw new \InvalidArgumentException("{$field} reservado: no se puede usar.");
            }
        }

        $pdo = Capsule::connection('default')->getPdo();
        $db = $site->db_name;
        $user = $pdo->quote($site->db_user);
        $pass = $pdo->quote($plainPassword);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("CREATE USER IF NOT EXISTS {$user}@'%' IDENTIFIED BY {$pass}");
        $pdo->exec("ALTER USER {$user}@'%' IDENTIFIED BY {$pass}");
        $pdo->exec("GRANT ALL PRIVILEGES ON `{$db}`.* TO {$user}@'%'");

        return [
            "Base de datos `{$db}` lista.",
            "Usuario MySQL {$site->db_user} con permisos sobre `{$db}`.",
        ];
    }

    /**
     * @return string[] log
     */
    public function initialize(Site $site, string $adminName, string $adminEmail, string $adminPassword): array
    {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email del administrador inválido.');
        }
        if (strlen($adminPassword) < 8) {
            throw new \InvalidArgumentException('La contraseña del administrador debe tener al menos 8 caracteres.');
        }

        $connName = $this->connections->connect($site);
        $conn = Capsule::connection($connName);
        $conn->getPdo(); // falla rápido con un error claro si la DB no responde

        // Sin el usuario inicial genérico: el admin del sitio se crea abajo con los datos del formulario
        $log = $this->migrations->migrate($conn, 'site', true, false);

        $conn->table('users')->updateOrInsert(
            ['email' => strtolower($adminEmail)],
            [
                'name'           => $adminName !== '' ? $adminName : 'Administrador',
                'password_hash'  => password_hash($adminPassword, PASSWORD_BCRYPT),
                'role'           => 'admin',
                'active'         => 1,
                'email_verified' => 1,
            ]
        );
        $log[] = "Administrador del sitio: {$adminEmail}";

        $conn->table('settings')->updateOrInsert(
            ['setting_key' => 'site_name'],
            ['setting_value' => $site->name]
        );

        $site->forceFill(['initialized_at' => date('Y-m-d H:i:s')])->save();
        $log[] = 'Sitio inicializado.';

        return $log;
    }
}
