<?php

namespace App\Services;

use App\Models\Server;

/**
 * Operaciones remotas sobre un servidor cPanel (desde el panel local, por SSH):
 *   - health():    estado del servidor y de la instalación remota
 *   - logs():      últimas líneas de logs permitidos
 *   - provision(): crea DB + usuario (uapi), registra e inicializa el sitio
 *                  usando `php bin/console` del servidor (con su propio .env)
 */
class ServerProvisioner
{
    private Server $server;
    private SshService $ssh;

    public function __construct(Server $server, ?SshService $ssh = null)
    {
        $this->server = $server;
        $this->ssh = $ssh ?? new SshService($server);
    }

    private function app(): string
    {
        return (string) $this->server->remote_app_path;
    }

    private function php(): string
    {
        return (string) ($this->server->php_bin ?: 'php');
    }

    // ─── Health ───────────────────────────────────────────────

    /** @return array{checks: array, sites: array} */
    public function health(): array
    {
        $checks = [];
        $sites = [];
        try {
            $this->ssh->connect();
            $checks[] = $this->check('ok', 'SSH', $this->server->username . '@' . $this->server->host . ' (' . $this->server->host_key_fingerprint . ')');
        } catch (\Throwable $e) {
            $this->markChecked(false);
            return ['checks' => [$this->check('error', 'SSH', $e->getMessage())], 'sites' => []];
        }

        $php = $this->ssh->exec('php_version', ['php' => $this->php()]);
        $checks[] = $this->check($php['code'] === 0 ? 'ok' : 'error', 'PHP CLI', strtok($php['out'] ?: $php['err'], "\n") ?: '—');

        $hasConsole = trim($this->ssh->exec('file_exists', ['path' => $this->app() . '/bin/console'])['out']) === 'yes';
        $checks[] = $hasConsole
            ? $this->check('ok', 'Aplicación', $this->app() . '/bin/console presente')
            : $this->check('error', 'Aplicación', 'No se encontró ' . $this->app() . '/bin/console. Desplegá esta versión y corré composer install.');

        if ($hasConsole) {
            $sys = $this->console(['system:check', '--json']);
            $remoteChecks = json_decode($sys['out'], true);
            if (is_array($remoteChecks)) {
                $errors = array_filter($remoteChecks, fn ($c) => ($c['status'] ?? '') === 'error');
                $warns = array_filter($remoteChecks, fn ($c) => ($c['status'] ?? '') === 'warn');
                $detail = count($errors) . ' error(es), ' . count($warns) . ' advertencia(s)';
                foreach (array_merge($errors, $warns) as $c) {
                    $detail .= "\n· {$c['label']}: {$c['detail']}";
                }
                $checks[] = $this->check($errors ? 'error' : ($warns ? 'warn' : 'ok'), 'Diagnóstico remoto', $detail);
            } else {
                $checks[] = $this->check('error', 'Diagnóstico remoto', trim($sys['err'] ?: $sys['out']) ?: 'Sin respuesta');
            }

            $fp = trim($this->console(['key:fingerprint'])['out']);
            $localKey = getenv('TENANT_ENCRYPTION_KEY') ?: ($_ENV['TENANT_ENCRYPTION_KEY'] ?? '');
            $local = SystemCheckService::keyFingerprint($localKey);
            $checks[] = $this->check('ok', 'Clave de cifrado', "Remota: {$fp} · Local: " . ($local ?: 'none')
                . ($fp === $local ? ' (iguales)' : ' (distintas: normal, cada instalación cifra con la suya)'));

            $list = json_decode($this->console(['site:list', '--json'])['out'], true);
            $sites = is_array($list) ? $list : [];
        }

        $quota = $this->uapi('Quota', 'get_quota_info');
        if ($quota['ok']) {
            $d = $quota['data'];
            $used = $d['megabytes_used'] ?? null;
            $limit = $d['megabyte_limit'] ?? null;
            $checks[] = $this->check('ok', 'Disco', $used !== null ? "{$used} MB usados" . ($limit ? " de {$limit} MB" : ' (sin límite)') : 'OK');
        } else {
            $checks[] = $this->check('warn', 'Disco (uapi)', $quota['error']);
        }

        $ok = !array_filter($checks, fn ($c) => $c['status'] === 'error');
        $this->markChecked($ok);

        return ['checks' => $checks, 'sites' => $sites];
    }

    // ─── Logs ─────────────────────────────────────────────────

    /** @return array<string,string> id => ruta remota (solo archivos permitidos) */
    public function logFiles(): array
    {
        $files = [];
        $dir = $this->app() . '/storage/logs';
        foreach (explode("\n", trim($this->ssh->exec('ls', ['dir' => $dir])['out'])) as $name) {
            $name = trim($name);
            if (preg_match('/^[A-Za-z0-9_.-]+\.log$/', $name)) {
                $files[$name] = "{$dir}/{$name}";
            }
        }
        foreach (['public/error_log' => $this->app() . '/public/error_log'] as $id => $path) {
            if (trim($this->ssh->exec('file_exists', ['path' => $path])['out']) === 'yes') {
                $files[$id] = $path;
            }
        }
        return $files;
    }

    public function tail(string $id, int $lines): string
    {
        $files = $this->logFiles();
        if (!isset($files[$id])) {
            throw new \InvalidArgumentException('Archivo de log no permitido.');
        }
        $lines = max(10, min(500, $lines));
        $res = $this->ssh->exec('tail', ['lines' => (string) $lines, 'file' => $files[$id]]);
        return $res['out'] !== '' ? $res['out'] : $res['err'];
    }

    // ─── Provisionar ──────────────────────────────────────────

    /**
     * @param array $in name, domain, db_suffix, user_suffix, admin_email, admin_name, admin_password, plan_days
     * @return array{ok:bool, steps: array<int, array{status:string, msg:string}>}
     */
    public function provision(array $in): array
    {
        $steps = [];
        $prefix = $this->server->dbPrefix();
        $db = $prefix . ($in['db_suffix'] ?? '');
        $user = $prefix . ($in['user_suffix'] ?? '');

        // Validaciones previas (no tocar nada si algo está mal)
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/D', $db)) {
            $errors[] = "Nombre de DB inválido: {$db} (solo letras, números y _, máx. 64).";
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,32}$/D', $user)) {
            $errors[] = "Usuario MySQL inválido: {$user} (solo letras, números y _, máx. 32 con prefijo).";
        }
        if (!preg_match(SiteValidator::DOMAIN_REGEX, strtolower((string) ($in['domain'] ?? '')))) {
            $errors[] = 'Dominio inválido.';
        }
        if (trim((string) ($in['name'] ?? '')) === '') {
            $errors[] = 'Nombre del sitio obligatorio.';
        }
        if (!filter_var($in['admin_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email del administrador inválido.';
        }
        if (strlen((string) ($in['admin_password'] ?? '')) < 8) {
            $errors[] = 'La contraseña del administrador debe tener al menos 8 caracteres.';
        }
        if ($errors) {
            return ['ok' => false, 'steps' => array_map(fn ($e) => ['status' => 'error', 'msg' => $e], $errors)];
        }

        $domain = strtolower($in['domain']);
        $dbPass = self::randomPassword(24);
        $tmp = '.landing-tmp/' . bin2hex(random_bytes(8));

        try {
            $this->ssh->connect();
            $steps[] = ['status' => 'ok', 'msg' => 'Conectado a ' . $this->server->host];

            if (trim($this->ssh->exec('file_exists', ['path' => $this->app() . '/bin/console'])['out']) !== 'yes') {
                throw new \RuntimeException('No existe ' . $this->app() . '/bin/console en el servidor: desplegá esta versión primero.');
            }

            // 1. Base de datos
            $r = $this->uapi('Mysql', 'create_database', ["name={$db}"]);
            $steps[] = $this->uapiStep($r, "Base de datos {$db}", true);

            // 2. Usuario (si existe, se le asigna la nueva contraseña)
            $r = $this->uapi('Mysql', 'create_user', ["name={$user}", "password={$dbPass}"]);
            if (!$r['ok'] && $this->alreadyExists($r['error'])) {
                $r = $this->uapi('Mysql', 'set_password', ["user={$user}", "password={$dbPass}"]);
                $steps[] = $this->uapiStep($r, "Usuario {$user} ya existía: contraseña regenerada", false);
            } else {
                $steps[] = $this->uapiStep($r, "Usuario MySQL {$user}", false);
            }

            // 3. Permisos
            $r = $this->uapi('Mysql', 'set_privileges_on_database', ["user={$user}", "database={$db}", 'privileges=ALL PRIVILEGES']);
            $steps[] = $this->uapiStep($r, "Permisos de {$user} sobre {$db}", false);

            // 4. Secretos para bin/console en archivos temporales 0600 (no por argv).
            //    uapi (pasos 2-3) no admite otra vía: la clave de DB va en su argv mientras corre.
            $this->ssh->exec('mkdir_tmp', ['dir' => $tmp]);
            $this->ssh->putSecret("{$tmp}/db", $dbPass);
            $this->ssh->putSecret("{$tmp}/admin", (string) $in['admin_password']);
            // bin/console corre con cwd = app: la ruta a tmp tiene que ser absoluta
            $abs = $this->absoluteTmp($tmp);

            // 5. Registrar en la DB master de producción
            $r = $this->console([
                'site:register',
                '--name=' . $in['name'],
                '--domain=' . $domain,
                '--db-host=' . ($this->server->mysql_host ?: 'localhost'),
                '--db-name=' . $db,
                '--db-user=' . $user,
                '--db-pass-file=' . "{$abs}/db",
                '--plan-days=' . max(1, (int) ($in['plan_days'] ?? 365)),
            ]);
            $steps[] = $this->consoleStep($r, 'Registro en el gestor de sitios de producción');

            // 6. Esquema + admin del sitio
            $r = $this->console([
                'site:init', $domain,
                '--admin-email=' . $in['admin_email'],
                '--admin-name=' . ($in['admin_name'] ?? ''),
                '--admin-pass-file=' . "{$abs}/admin",
            ], 180);
            $steps[] = $this->consoleStep($r, 'Inicialización del sitio');

            $steps[] = ['status' => 'warn', 'msg' => "Falta: en cPanel → Dominios, agregá {$domain} con la misma raíz de documentos que el dominio master y emití SSL. Luego verificá desde el gestor de sitios de producción."];
            $ok = true;
        } catch (\Throwable $e) {
            $steps[] = ['status' => 'error', 'msg' => $e->getMessage()];
            $ok = false;
        } finally {
            try {
                $this->ssh->exec('rm_tmp', ['dir' => $tmp]);
            } catch (\Throwable $e) {
                $steps[] = ['status' => 'warn', 'msg' => "No se pudo borrar {$tmp} en el servidor: borralo a mano."];
            }
            $this->ssh->disconnect();
        }

        return ['ok' => $ok, 'steps' => $steps];
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function console(array $args, int $timeout = 60): array
    {
        return $this->ssh->exec('console', ['app' => $this->app(), 'php' => $this->php(), 'args' => $args], $timeout);
    }

    /** @return array{ok:bool, data:mixed, error:string} */
    private function uapi(string $module, string $function, array $params = []): array
    {
        $r = $this->ssh->exec('uapi', ['module' => $module, 'function' => $function, 'params' => $params]);
        $json = json_decode($r['out'], true);
        $result = $json['result'] ?? null;
        if (!is_array($result)) {
            return ['ok' => false, 'data' => null, 'error' => trim($r['err'] ?: $r['out']) ?: 'uapi no respondió (¿es un servidor cPanel?)'];
        }
        $ok = (int) ($result['status'] ?? 0) === 1;
        return [
            'ok'    => $ok,
            'data'  => $result['data'] ?? null,
            'error' => $ok ? '' : implode(' ', (array) ($result['errors'] ?? ['Error desconocido'])),
        ];
    }

    private function uapiStep(array $r, string $label, bool $toleratesExisting): array
    {
        if ($r['ok']) {
            return ['status' => 'ok', 'msg' => $label];
        }
        if ($toleratesExisting && $this->alreadyExists($r['error'])) {
            return ['status' => 'ok', 'msg' => "{$label} (ya existía)"];
        }
        throw new \RuntimeException("{$label}: {$r['error']}");
    }

    private function consoleStep(array $r, string $label): array
    {
        if ($r['code'] !== 0) {
            throw new \RuntimeException("{$label}: " . (trim($r['err'] ?: $r['out']) ?: "exit {$r['code']}"));
        }
        return ['status' => 'ok', 'msg' => $label . ': ' . trim(str_replace("\n", ' · ', trim($r['out'])))];
    }

    private function alreadyExists(string $error): bool
    {
        return (bool) preg_match('/already exists|ya existe|exists/i', $error);
    }

    /** Ruta absoluta del directorio temporal (bin/console corre con cwd = app). */
    private function absoluteTmp(string $tmp): string
    {
        if (trim($this->ssh->exec('file_exists', ['path' => $tmp . '/db'])['out']) !== 'yes') {
            throw new \RuntimeException('No se pudo crear el directorio temporal remoto.');
        }
        // La sesión SSH arranca en $HOME (en cPanel no siempre es /home/<usuario>)
        $home = rtrim(trim($this->ssh->exec('pwd')['out']), '/');
        if ($home === '' || $home[0] !== '/') {
            throw new \RuntimeException('No se pudo determinar el directorio home remoto.');
        }
        return $home . '/' . $tmp;
    }

    private function markChecked(bool $ok): void
    {
        $this->server->forceFill(['last_check_at' => date('Y-m-d H:i:s'), 'last_check_ok' => $ok ? 1 : 0])->save();
    }

    private function check(string $status, string $label, string $detail): array
    {
        return ['status' => $status, 'label' => $label, 'detail' => $detail];
    }

    public static function randomPassword(int $len): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
