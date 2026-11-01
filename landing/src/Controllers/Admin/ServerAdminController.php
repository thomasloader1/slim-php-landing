<?php

namespace App\Controllers\Admin;

use App\Models\Server;
use App\Services\EncryptionService;
use App\Services\ServerProvisioner;
use App\Services\SshService;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * /admin/servers — servidores de producción (cPanel) administrados por SSH con clave.
 * Pensado para usarse desde el panel LOCAL: health, logs y provisionar sitios remotos.
 */
class ServerAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/servers/index', [
            'servers' => Server::orderBy('name')->get(),
            'keysDir' => SshService::keysDir(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->form($response, null);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        if ($error = $this->validate($data)) {
            return $this->backWithError($response, 'admin/servers/create', $error, $data);
        }
        $server = Server::create($this->fields($data));
        $_SESSION['flash_success'] = 'Servidor guardado. Ahora verificá y confirmá su huella SSH.';
        return $response->withHeader('Location', url('admin/servers/' . $server->id))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        return $this->form($response, Server::findOrFail($args['id']));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $server = Server::findOrFail($args['id']);
        $data = (array) $request->getParsedBody();
        if ($error = $this->validate($data)) {
            return $this->backWithError($response, 'admin/servers/edit/' . $server->id, $error, $data);
        }
        $fields = $this->fields($data, $server);
        // Cambió el destino: la huella confirmada ya no aplica
        if ($fields['host'] !== $server->host || (int) $fields['port'] !== (int) $server->port) {
            $fields['host_key_fingerprint'] = null;
        }
        $server->update($fields);
        $_SESSION['flash_success'] = 'Servidor actualizado.';
        return $response->withHeader('Location', url('admin/servers/' . $server->id))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Server::destroy($args['id']);
        $_SESSION['flash_success'] = 'Servidor eliminado (no se tocó nada en el servidor remoto).';
        return $response->withHeader('Location', url('admin/servers'))->withStatus(302);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $server = Server::findOrFail($args['id']);
        $provision = $_SESSION['provision_result'] ?? null;
        unset($_SESSION['provision_result']);

        return $this->render($response, 'admin/servers/show', [
            'server'    => $server,
            'provision' => $provision,
            'old'       => $this->pullOld(),
        ]);
    }

    /** AJAX: obtiene la huella del host (sin autenticar) para que el usuario la confirme. */
    public function fingerprint(Request $request, Response $response, array $args): Response
    {
        $server = Server::findOrFail($args['id']);
        try {
            $fp = (new SshService($server))->fetchHostFingerprint();
            return $this->json($response, [
                'success'     => true,
                'fingerprint' => $fp,
                'pinned'      => $server->host_key_fingerprint,
                'matches'     => $server->host_key_fingerprint === $fp,
            ]);
        } catch (\Throwable $e) {
            return $this->json($response, ['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Confirma la huella: se vuelve a leer y debe coincidir con la que vio el usuario. */
    public function pin(Request $request, Response $response, array $args): Response
    {
        $server = Server::findOrFail($args['id']);
        $seen = (string) (((array) $request->getParsedBody())['fingerprint'] ?? '');
        try {
            $actual = (new SshService($server))->fetchHostFingerprint();
            if (!hash_equals($actual, $seen)) {
                $_SESSION['flash_error'] = 'La huella cambió entre la verificación y la confirmación. Volvé a verificar.';
            } else {
                $server->update(['host_key_fingerprint' => $actual]);
                $_SESSION['flash_success'] = "Huella confirmada: {$actual}";
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo confirmar la huella: ' . $e->getMessage();
        }
        return $response->withHeader('Location', url('admin/servers/' . $server->id))->withStatus(302);
    }

    /** AJAX: estado del servidor y de la instalación remota. */
    public function health(Request $request, Response $response, array $args): Response
    {
        @set_time_limit(120);
        $server = Server::findOrFail($args['id']);
        try {
            return $this->json($response, ['success' => true] + (new ServerProvisioner($server))->health());
        } catch (\Throwable $e) {
            return $this->json($response, ['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** AJAX: lista de logs permitidos o tail de uno de ellos. */
    public function logs(Request $request, Response $response, array $args): Response
    {
        @set_time_limit(60);
        $server = Server::findOrFail($args['id']);
        $body = (array) $request->getParsedBody();
        try {
            $p = new ServerProvisioner($server);
            $file = (string) ($body['file'] ?? '');
            if ($file === '') {
                return $this->json($response, ['success' => true, 'files' => array_keys($p->logFiles())]);
            }
            return $this->json($response, ['success' => true, 'file' => $file, 'content' => $p->tail($file, (int) ($body['lines'] ?? 100))]);
        } catch (\Throwable $e) {
            return $this->json($response, ['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Provisiona un sitio en el servidor (DB + usuario + registro + inicialización). */
    public function provision(Request $request, Response $response, array $args): Response
    {
        @set_time_limit(300);
        $server = Server::findOrFail($args['id']);
        $data = (array) $request->getParsedBody();

        $result = (new ServerProvisioner($server))->provision($data);
        $_SESSION['provision_result'] = $result;

        if (!$result['ok']) {
            unset($data['admin_password'], $data['_token']);
            $_SESSION['server_old'] = $data;
        }
        error_log(sprintf('[ServerAdmin][AUDIT] provision %s en server %d: %s',
            $data['domain'] ?? '?', $server->id, $result['ok'] ? 'OK' : 'ERROR'));

        return $response->withHeader('Location', url('admin/servers/' . $server->id) . '#provision')->withStatus(302);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function form(Response $response, ?Server $server): Response
    {
        return $this->render($response, 'admin/servers/form', [
            'server'  => $server,
            'keys'    => SshService::availableKeys(),
            'keysDir' => SshService::keysDir(),
            'old'     => $this->pullOld(),
        ]);
    }

    private function validate(array $d): ?string
    {
        if (trim((string) ($d['name'] ?? '')) === '') {
            return 'El nombre es obligatorio.';
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]{0,254}$/D', (string) ($d['host'] ?? ''))) {
            return 'Host inválido (dominio o IP).';
        }
        $port = (int) ($d['port'] ?? 22);
        if ($port < 1 || $port > 65535) {
            return 'Puerto inválido.';
        }
        if (!preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}$/', (string) ($d['username'] ?? ''))) {
            return 'Usuario SSH inválido.';
        }
        if ($err = SshService::validateKeyPath((string) ($d['key_path'] ?? ''))) {
            return $err;
        }
        if (($d['cpanel_user'] ?? '') !== '' && !preg_match('/^[A-Za-z0-9_]{1,16}$/', (string) $d['cpanel_user'])) {
            return 'Usuario cPanel inválido.';
        }
        $path = (string) ($d['remote_app_path'] ?? '');
        if (!preg_match('#^[A-Za-z0-9_][A-Za-z0-9_./-]{0,499}$#D', $path) || str_contains($path, '..')) {
            return 'Ruta de la app inválida (relativa al home, ej: landing).';
        }
        if (!preg_match('#^[A-Za-z0-9_/][A-Za-z0-9_./-]{0,199}$#D', (string) ($d['php_bin'] ?? 'php'))) {
            return 'Binario PHP inválido (ej: php o /opt/cpanel/ea-php81/root/usr/bin/php).';
        }
        if (!preg_match('/^[A-Za-z0-9.-]{1,255}$/', (string) ($d['mysql_host'] ?? 'localhost'))) {
            return 'Host MySQL inválido.';
        }
        return null;
    }

    private function fields(array $d, ?Server $current = null): array
    {
        $fields = [
            'name'            => trim((string) $d['name']),
            'host'            => trim((string) $d['host']),
            'port'            => (int) ($d['port'] ?? 22),
            'username'        => trim((string) $d['username']),
            'key_path'        => (string) $d['key_path'],
            'cpanel_user'     => ($d['cpanel_user'] ?? '') !== '' ? $d['cpanel_user'] : null,
            'remote_app_path' => rtrim((string) $d['remote_app_path'], '/'),
            'php_bin'         => (string) ($d['php_bin'] ?: 'php'),
            'mysql_host'      => (string) ($d['mysql_host'] ?: 'localhost'),
        ];
        if (!empty($d['clear_passphrase'])) {
            $fields['key_passphrase_encrypted'] = null;
        } elseif (($d['key_passphrase'] ?? '') !== '') {
            $fields['key_passphrase_encrypted'] = (new EncryptionService())->encrypt((string) $d['key_passphrase']);
        }
        return $fields;
    }

    private function backWithError(Response $response, string $path, string $error, array $data): Response
    {
        unset($data['key_passphrase'], $data['_token']);
        $_SESSION['flash_error'] = $error;
        $_SESSION['server_old'] = $data;
        return $response->withHeader('Location', url($path))->withStatus(302);
    }

    private function pullOld(): array
    {
        $old = $_SESSION['server_old'] ?? [];
        unset($_SESSION['server_old']);
        return $old;
    }

    private function json(Response $response, array $data): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
