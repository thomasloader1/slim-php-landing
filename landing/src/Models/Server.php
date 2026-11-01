<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Servidor remoto (cPanel) administrado por SSH desde el panel local.
 * Ver db/master/02_servers.sql.
 */
class Server extends Model
{
    protected $table = 'servers';

    protected $fillable = [
        'name', 'host', 'port', 'username', 'key_path', 'key_passphrase_encrypted',
        'host_key_fingerprint', 'cpanel_user', 'remote_app_path', 'php_bin', 'mysql_host',
        'last_check_at', 'last_check_ok',
    ];

    protected $hidden = ['key_passphrase_encrypted'];

    protected $casts = [
        'port'          => 'integer',
        'last_check_at' => 'datetime',
        'last_check_ok' => 'boolean',
    ];

    /** Prefijo que cPanel antepone a bases y usuarios MySQL (cuenta_). */
    public function dbPrefix(): string
    {
        return ($this->cpanel_user ?: $this->username) . '_';
    }
}
