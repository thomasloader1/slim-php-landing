<?php

namespace Tests\Unit\Services;

use App\Services\TenantDoctorService as Doctor;
use PHPUnit\Framework\TestCase;

/** Diagnóstico del error "Tenant resolution unavailable: encryption key not configured". */
class TenantDoctorServiceTest extends TestCase
{
    private string|false $prevMaster;

    protected function setUp(): void
    {
        $this->prevMaster = getenv('MASTER_DOMAINS');
        putenv('MASTER_DOMAINS=localhost');
    }

    protected function tearDown(): void
    {
        $this->prevMaster === false ? putenv('MASTER_DOMAINS') : putenv('MASTER_DOMAINS=' . $this->prevMaster);
    }

    private function state(array $over = []): array
    {
        return $over + [
            'module' => true, 'sites' => 0, 'site_domains' => [], 'has_key' => false,
            'master_domains' => ['localhost', '127.0.0.1'], 'app_env' => 'production',
        ];
    }

    public function test_resolve_mirrors_the_middleware(): void
    {
        $this->assertSame(Doctor::OUTCOME_BLOCKED_KEY, Doctor::resolve('gomepab.com.ar', $this->state()));
        $this->assertSame(Doctor::OUTCOME_BLOCKED_KEY, Doctor::resolve('www.GOMEPAB.com.ar:443', $this->state()));
        $this->assertSame(Doctor::OUTCOME_MASTER, Doctor::resolve('localhost', $this->state()));
        $this->assertSame(Doctor::OUTCOME_MASTER_OFF, Doctor::resolve('gomepab.com.ar', $this->state(['module' => false])));
        $this->assertSame(Doctor::OUTCOME_UNKNOWN, Doctor::resolve('gomepab.com.ar', $this->state(['has_key' => true])));
        $this->assertSame(Doctor::OUTCOME_SITE, Doctor::resolve('cliente.com', $this->state(['has_key' => true, 'site_domains' => ['cliente.com']])));
    }

    public function test_plan_for_main_domain_without_sites(): void
    {
        $plan = Doctor::plan($this->state(), 'www.gomepab.com.ar');
        $this->assertSame(['add_master', 'disable_module'], array_column($plan, 'type'));
        $this->assertSame('localhost,gomepab.com.ar', $plan[0]['value'], 'conserva los dominios ya configurados');
    }

    public function test_plan_never_invents_a_key_when_sites_exist(): void
    {
        $plan = Doctor::plan($this->state(['sites' => 2, 'site_domains' => ['cliente.com']]), 'cliente.com');
        $this->assertSame(['manual'], array_column($plan, 'type'), 'un sitio cliente no se pasa a master');
        $this->assertStringContainsString('ORIGINAL', $plan[0]['detail']);
    }

    public function test_plan_is_empty_when_everything_works(): void
    {
        $this->assertSame([], Doctor::plan($this->state(['master_domains' => ['localhost', 'gomepab.com.ar'], 'has_key' => true]), 'gomepab.com.ar'));
        $this->assertSame([], Doctor::plan($this->state(['module' => false]), 'gomepab.com.ar'));
    }

    public function test_env_file_is_updated_in_place_with_backup_and_permissions(): void
    {
        $dir = sys_get_temp_dir() . '/doctor-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $env = $dir . '/.env';
        file_put_contents($env, "APP_ENV=production\nMASTER_DOMAINS=localhost\nDB_PASS=\"x y\"\n");
        chmod($env, 0600);

        $backup = Doctor::writeEnvValue($env, 'MASTER_DOMAINS', 'localhost,gomepab.com.ar');
        $this->assertSame("APP_ENV=production\nMASTER_DOMAINS=localhost,gomepab.com.ar\nDB_PASS=\"x y\"\n", file_get_contents($env));
        $this->assertSame("APP_ENV=production\nMASTER_DOMAINS=localhost\nDB_PASS=\"x y\"\n", file_get_contents($backup));
        $this->assertSame(0600, fileperms($env) & 0777);
        $this->assertSame(0600, fileperms($backup) & 0777);
        $this->assertSame('localhost,gomepab.com.ar', Doctor::readEnvValue($env, 'MASTER_DOMAINS'));

        Doctor::writeEnvValue($env, 'NEW_KEY', 'v');
        $this->assertSame('v', Doctor::readEnvValue($env, 'NEW_KEY'));

        foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
            unlink($dir . '/' . $f);
        }
        @rmdir($dir);
    }

    public function test_env_writer_rejects_injection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Doctor::writeEnvValue(sys_get_temp_dir() . '/nope.env', 'MASTER_DOMAINS', "a\nTENANT_ENCRYPTION_KEY=x");
    }
}
