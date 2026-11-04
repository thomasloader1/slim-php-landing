<?php

namespace Tests\Unit\Services;

use App\Services\EncryptionService;
use App\Services\MigrationService;
use App\Services\SsoTokenService;
use App\Support\SqlSplitter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

class SiteManagementServicesTest extends TestCase
{
    private const KEY = 'test-encryption-key-32chars-long!';

    protected function tearDown(): void
    {
        Capsule::table('sso_nonces')->delete();
        Capsule::table('settings')->where('setting_key', 'like', 'sso_nonce:%')->delete();
        parent::tearDown();
    }

    /** @test */
    public function splitter_respects_quotes_and_comments(): void
    {
        $sql = "-- comentario; con punto y coma\n"
            . "INSERT INTO t VALUES ('a;b', \"c;d\", 'it''s; ok', 'esc\\'; x');\n"
            . "/* bloque; */ CREATE TABLE `x;y` (id INT);\n"
            . "# otro; comentario\n"
            . "SELECT 1";

        $out = SqlSplitter::split($sql);

        $this->assertCount(3, $out);
        $this->assertStringContainsString("'a;b'", $out[0]);
        $this->assertStringContainsString("'it''s; ok'", $out[0]);
        $this->assertStringContainsString('`x;y`', $out[1]);
        $this->assertSame('SELECT 1', $out[2]);
    }

    /** @test */
    public function splitter_parses_real_schema_files(): void
    {
        $root = dirname(__DIR__, 3) . '/db/';
        foreach ((new MigrationService())->filesFor('master') as $f) {
            $stmts = SqlSplitter::split(file_get_contents($root . $f));
            $this->assertNotEmpty($stmts, $f);
            foreach ($stmts as $s) {
                $this->assertMatchesRegularExpression('/^(CREATE|ALTER|INSERT|UPDATE|DELETE|SET|PREPARE|EXECUTE|DEALLOCATE)\b/i', $s, "{$f}: sentencia inesperada");
            }
        }
    }

    /** @test */
    public function migrate_is_idempotent_and_never_adds_users_when_some_exist(): void
    {
        $conn = Capsule::connection('default');
        $svc = new MigrationService();
        $usersBefore = $conn->table('users')->count();

        $svc->migrate($conn, 'master', true);
        $log = $svc->migrate($conn, 'master', true); // segunda vez: no debe fallar

        $this->assertNotEmpty($log);
        $this->assertSame([], $svc->missingTables($conn, 'master'));
        $this->assertSame($usersBefore, $conn->table('users')->count());
        foreach ($svc->status($conn, 'master') as $m) {
            $this->assertSame('applied', $m['status'], $m['file']);
        }
    }

    /** @test */
    public function sso_token_is_single_use_and_bound_to_site(): void
    {
        $master = Capsule::connection('default');
        $sso = new SsoTokenService(self::KEY);

        $token = $sso->issue(7, $master);
        $this->assertNull($sso->consume($token, 8, $master), 'otro sitio');
        $this->assertSame(7, $sso->consume($token, 7, $master));
        $this->assertNull($sso->consume($token, 7, $master), 'reuso');
    }

    /** @test */
    public function sso_nonce_is_stored_in_sso_nonces_and_deleted_on_consume(): void
    {
        // El nonce vive en la tabla sso_nonces (DB master), no en settings: una copia
        // de sitio hijo la consume vía la conexión `master` con un usuario mínimo.
        $master = Capsule::connection('default');
        $sso = new SsoTokenService(self::KEY);

        $before = (int) $master->table('sso_nonces')->count();
        $token = $sso->issue(11, $master);
        $this->assertSame($before + 1, (int) $master->table('sso_nonces')->count(), 'nonce registrado al emitir');

        $this->assertSame(11, $sso->consume($token, 11, $master));
        $this->assertSame($before, (int) $master->table('sso_nonces')->count(), 'nonce borrado al consumir');
    }

    /** @test */
    public function sso_rejects_expired_tokens(): void
    {
        $master = Capsule::connection('default');
        $sso = new SsoTokenService(self::KEY);

        // Token firmado con la clave correcta pero vencido (exp en el pasado)
        $nonce = str_repeat('c', 32);
        $master->table('sso_nonces')->insert(['nonce' => $nonce, 'expires_at' => date('Y-m-d H:i:s', time() + 60)]);

        $payload = rtrim(strtr(base64_encode(json_encode(['s' => 5, 'e' => time() - 5, 'n' => $nonce])), '+/', '-_'), '=');
        $key = (new \ReflectionProperty(SsoTokenService::class, 'key'))->getValue($sso);
        $token = $payload . '.' . rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, $key, true)), '+/', '-_'), '=');

        $this->assertNull($sso->consume($token, 5, $master), 'token vencido');
        // El chequeo de expiración va antes del consumo: el nonce sigue ahí
        $this->assertSame(1, $master->table('sso_nonces')->where('nonce', $nonce)->delete());
    }

    /** @test */
    public function sso_rejects_tampered_or_foreign_tokens(): void
    {
        $master = Capsule::connection('default');
        $token = (new SsoTokenService(self::KEY))->issue(3, $master);

        [$payload, $sig] = explode('.', $token);
        $forged = rtrim(strtr(base64_encode(json_encode(['s' => 4, 'e' => time() + 60, 'n' => str_repeat('a', 32)])), '+/', '-_'), '=');

        $sso = new SsoTokenService(self::KEY);
        $this->assertNull($sso->consume($forged . '.' . $sig, 4, $master));
        $this->assertNull((new SsoTokenService('otra-clave-distinta-de-32-bytes!!'))->consume($token, 3, $master));
        $this->assertNull($sso->consume('basura', 3, $master));
    }

    /** @test */
    public function explicit_active_key_version_wins_over_env(): void
    {
        putenv('TENANT_ENCRYPTION_KEY_ACTIVE=v1');
        $v2 = new EncryptionService(self::KEY, 'otra-clave-de-al-menos-32-bytes!!', 'v2');

        $cipher = $v2->encrypt('secreto');

        $this->assertStringStartsWith('v2:', $cipher);
        $this->assertSame('secreto', $v2->decrypt($cipher));
        putenv('TENANT_ENCRYPTION_KEY_ACTIVE');
    }
}
