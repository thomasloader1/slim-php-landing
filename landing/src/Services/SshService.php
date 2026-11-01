<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

/**
 * Conexión SSH (clave privada) a un servidor remoto.
 *
 * Seguridad:
 *  - La clave privada solo se lee desde SSH_KEYS_DIR (montado read-only), nunca de la DB.
 *  - Host key pinning: hay que confirmar la huella SHA256 una vez; si cambia, se rechaza.
 *  - Solo se ejecutan comandos de la whitelist COMMANDS; todos los argumentos van por
 *    escapeshellarg(). No existe un "ejecutar comando libre".
 */
class SshService
{
    /**
     * Plantillas permitidas. {x} = argumento escapado; {x*} = lista de argumentos escapados.
     */
    private const COMMANDS = [
        'pwd'         => 'pwd',
        'php_version' => '{php} -v',
        'file_exists' => 'test -f {path} && echo yes || echo no',
        'console'     => 'cd {app} && {php} bin/console {args*}',
        'uapi'        => 'uapi --output=json {module} {function} {params*}',
        'ls'          => 'ls -1t {dir} 2>/dev/null | head -n 50',
        'tail'        => 'tail -n {lines} {file}',
        'mkdir_tmp'   => 'umask 077 && mkdir -p {dir}',
        'rm_tmp'      => 'rm -rf {dir}',
    ];

    private Server $server;
    private EncryptionService $encryption;
    private ?SFTP $conn = null;

    public function __construct(Server $server, ?EncryptionService $encryption = null)
    {
        $this->server = $server;
        $this->encryption = $encryption ?? new EncryptionService();
    }

    public static function keysDir(): string
    {
        $dir = getenv('SSH_KEYS_DIR') ?: ($_ENV['SSH_KEYS_DIR'] ?? '/run/secrets/ssh');
        return rtrim($dir, '/');
    }

    /**
     * Valida que la ruta de la clave esté dentro de SSH_KEYS_DIR y sea legible.
     * @return string|null error o null si es válida
     */
    public static function validateKeyPath(string $path): ?string
    {
        $base = realpath(self::keysDir());
        if ($base === false) {
            return 'SSH_KEYS_DIR (' . self::keysDir() . ') no existe en el contenedor. Montá la carpeta de claves (ver docker-compose.yml).';
        }
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, $base . '/')) {
            return 'La clave debe estar dentro de ' . self::keysDir() . '.';
        }
        if (!is_file($real) || !is_readable($real)) {
            return 'No se puede leer la clave ' . $path . ' (¿permisos?).';
        }
        return null;
    }

    /** @return string[] claves disponibles en SSH_KEYS_DIR (para el selector del formulario) */
    public static function availableKeys(): array
    {
        $dir = self::keysDir();
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f[0] !== '.' && !str_ends_with($f, '.pub') && is_file("{$dir}/{$f}")) {
                $out[] = "{$dir}/{$f}";
            }
        }
        return $out;
    }

    /** Huella SHA256 (formato OpenSSH) de la clave de host, sin autenticar. */
    public function fetchHostFingerprint(): string
    {
        $ssh = $this->newConnection();
        $hostKey = $ssh->getServerPublicHostKey();
        if (!$hostKey) {
            throw new \RuntimeException('No se pudo obtener la clave de host de ' . $this->server->host);
        }
        $ssh->disconnect();
        return self::fingerprint($hostKey);
    }

    public static function fingerprint(string $hostKey): string
    {
        $parts = explode(' ', trim($hostKey));
        $blob = base64_decode($parts[1] ?? $parts[0], true) ?: $hostKey;
        return 'SHA256:' . rtrim(base64_encode(hash('sha256', $blob, true)), '=');
    }

    /** Conecta y autentica. Exige huella confirmada y coincidente. */
    public function connect(): void
    {
        if ($this->conn !== null) {
            return;
        }
        $pinned = (string) $this->server->host_key_fingerprint;
        if ($pinned === '') {
            throw new \RuntimeException('Primero confirmá la huella del servidor (botón "Verificar huella").');
        }
        if ($err = self::validateKeyPath((string) $this->server->key_path)) {
            throw new \RuntimeException($err);
        }

        $ssh = $this->newConnection();
        $actual = self::fingerprint((string) $ssh->getServerPublicHostKey());
        if (!hash_equals($pinned, $actual)) {
            $ssh->disconnect();
            throw new \RuntimeException("La clave de host cambió (esperada {$pinned}, recibida {$actual}). Posible ataque MITM o servidor reinstalado: verificá y volvé a confirmar la huella.");
        }

        $passphrase = $this->server->key_passphrase_encrypted
            ? $this->encryption->decrypt($this->server->key_passphrase_encrypted)
            : false;
        $key = PublicKeyLoader::load((string) file_get_contents($this->server->key_path), $passphrase);

        if (!$ssh->login($this->server->username, $key)) {
            throw new \RuntimeException('Autenticación SSH rechazada para ' . $this->server->username . '@' . $this->server->host . '. ¿Está la clave pública en cPanel → Acceso SSH → Administrar claves (autorizada)?');
        }
        $this->conn = $ssh;
    }

    /**
     * Ejecuta un comando de la whitelist.
     * @return array{code:int, out:string, err:string}
     */
    public function exec(string $name, array $vars = [], int $timeout = 60): array
    {
        if (!isset(self::COMMANDS[$name])) {
            throw new \InvalidArgumentException("Comando SSH no permitido: {$name}");
        }
        $this->connect();

        $cmd = preg_replace_callback('/\{([a-z]+)(\*?)\}/', function ($m) use ($vars, $name) {
            if (!array_key_exists($m[1], $vars)) {
                throw new \InvalidArgumentException("Falta {$m[1]} para {$name}");
            }
            $v = $vars[$m[1]];
            return $m[2] === '*'
                ? implode(' ', array_map('escapeshellarg', array_map('strval', (array) $v)))
                : escapeshellarg((string) $v);
        }, self::COMMANDS[$name]);

        $this->conn->setTimeout($timeout);
        $this->conn->enableQuietMode(); // stderr separado
        $out = (string) $this->conn->exec($cmd);
        $err = (string) $this->conn->getStdError();
        $code = $this->conn->getExitStatus();
        $this->conn->disableQuietMode();

        if ($this->conn->isTimeout()) {
            throw new \RuntimeException("Timeout ejecutando {$name} ({$timeout}s)");
        }

        return ['code' => $code === false ? -1 : (int) $code, 'out' => $out, 'err' => $err];
    }

    /** Sube contenido a un archivo remoto con permisos 0600. */
    public function putSecret(string $remotePath, string $content): void
    {
        $this->connect();
        if (!$this->conn->put($remotePath, $content)) {
            throw new \RuntimeException("No se pudo subir {$remotePath} por SFTP");
        }
        $this->conn->chmod(0600, $remotePath);
    }

    public function disconnect(): void
    {
        $this->conn?->disconnect();
        $this->conn = null;
    }

    private function newConnection(): SFTP
    {
        $ssh = new SFTP($this->server->host, (int) ($this->server->port ?: 22), 15);
        return $ssh;
    }
}
