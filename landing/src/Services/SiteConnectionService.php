<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Database\Capsule\Manager as Capsule;

class SiteConnectionService
{
    private EncryptionService $encryption;

    public function __construct(EncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Abre una conexión Eloquent nombrada hacia la DB del sitio remoto.
     * Retorna el nombre de la conexión.
     */
    public function connect(Site $site, string $encryptionKey): string
    {
        $connectionName = 'site_' . $site->id;

        $password = $this->encryption->decrypt($site->db_pass_encrypted, $encryptionKey);

        Capsule::addConnection([
            'driver'    => 'mysql',
            'host'      => $site->db_host,
            'port'      => $site->db_port ?? 3306,
            'database'  => $site->db_name,
            'username'  => $site->db_user,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ], $connectionName);

        return $connectionName;
    }

    /**
     * Prueba la conexión a la DB del sitio remoto.
     */
    public function testConnection(Site $site, string $encryptionKey): bool
    {
        try {
            $connName = $this->connect($site, $encryptionKey);
            Capsule::connection($connName)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Escribe site_status y site_redirect_url en la DB del landing remoto.
     */
    public function syncStatus(Site $site, string $encryptionKey): void
    {
        $connName = $this->connect($site, $encryptionKey);
        $connection = Capsule::connection($connName);

        $connection->table('settings')->updateOrInsert(
            ['setting_key' => 'site_status'],
            ['setting_value' => $site->status, 'updated_at' => date('Y-m-d H:i:s')]
        );

        $redirectUrl = $site->status === 'suspended' ? $site->redirect_url : '';
        $connection->table('settings')->updateOrInsert(
            ['setting_key' => 'site_redirect_url'],
            ['setting_value' => $redirectUrl, 'updated_at' => date('Y-m-d H:i:s')]
        );

        $site->update(['last_status_sync' => now()]);
    }
}
