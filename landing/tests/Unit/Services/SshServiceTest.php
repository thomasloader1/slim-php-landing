<?php

namespace Tests\Unit\Services;

use App\Models\Server;
use App\Services\ServerProvisioner;
use App\Services\SshService;
use PHPUnit\Framework\TestCase;

/** Sin red: valida las protecciones que no dependen de un servidor SSH real. */
class SshServiceTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/sshkeys-' . uniqid();
        mkdir($this->dir . '/sub', 0700, true);
        file_put_contents($this->dir . '/id_test', 'KEY');
        file_put_contents($this->dir . '/id_test.pub', 'PUB');
        putenv('SSH_KEYS_DIR=' . $this->dir);
    }

    protected function tearDown(): void
    {
        foreach (['/id_test', '/id_test.pub'] as $f) {
            @unlink($this->dir . $f);
        }
        @rmdir($this->dir . '/sub');
        @rmdir($this->dir);
        putenv('SSH_KEYS_DIR');
    }

    /** @test */
    public function key_must_live_inside_keys_dir(): void
    {
        $this->assertNull(SshService::validateKeyPath($this->dir . '/id_test'));
        $this->assertNotNull(SshService::validateKeyPath('/etc/passwd'));
        $this->assertNotNull(SshService::validateKeyPath($this->dir . '/sub/../../../etc/passwd'));
        $this->assertNotNull(SshService::validateKeyPath($this->dir . '/no-existe'));
        $this->assertNotNull(SshService::validateKeyPath($this->dir . '/sub'), 'un directorio no es una clave');
    }

    /** @test */
    public function available_keys_excludes_public_keys(): void
    {
        $this->assertSame([$this->dir . '/id_test'], SshService::availableKeys());
    }

    /** @test */
    public function fingerprint_matches_openssh_format(): void
    {
        // ssh-keygen -lf de esta clave pública da SHA256:<base64 sin padding del sha256 del blob>
        $blob = random_bytes(51);
        $expected = 'SHA256:' . rtrim(base64_encode(hash('sha256', $blob, true)), '=');
        $this->assertSame($expected, SshService::fingerprint('ssh-ed25519 ' . base64_encode($blob) . ' comment'));
    }

    /** @test */
    public function only_whitelisted_commands_are_accepted(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new SshService(new Server(['host' => 'x', 'username' => 'u'])))->exec('rm -rf /');
    }

    /** @test */
    public function connect_refuses_without_pinned_fingerprint(): void
    {
        $this->expectExceptionMessage('confirmá la huella');
        (new SshService(new Server(['host' => 'x', 'username' => 'u', 'key_path' => $this->dir . '/id_test'])))->connect();
    }

    /** @test */
    public function provision_validates_before_touching_the_server(): void
    {
        $server = new Server(['host' => 'x', 'username' => 'cuenta', 'remote_app_path' => 'landing']);
        $ssh = $this->createMock(SshService::class);
        $ssh->expects($this->never())->method('connect');
        $ssh->expects($this->never())->method('exec');

        $r = (new ServerProvisioner($server, $ssh))->provision([
            'name' => 'X', 'domain' => 'http://mal', 'db_suffix' => 'a;drop', 'user_suffix' => str_repeat('u', 40),
            'admin_email' => 'no-es-email', 'admin_password' => 'corta',
        ]);

        $this->assertFalse($r['ok']);
        $this->assertCount(5, $r['steps']);
    }

    /** @test */
    public function random_password_is_alphanumeric(): void
    {
        $p = ServerProvisioner::randomPassword(24);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $p);
        $this->assertNotSame($p, ServerProvisioner::randomPassword(24));
    }
}
