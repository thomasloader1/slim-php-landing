<?php

namespace Tests\Unit\Services;

use App\Services\DirectoryListingService;
use PHPUnit\Framework\TestCase;

class DirectoryListingServiceTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/dirlist_' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/zeta.com/landing', 0777, true);
        mkdir($this->dir . '/alpha/sub', 0777, true);
        mkdir($this->dir . '/cliente.com.ar', 0777, true);
        touch($this->dir . '/cliente.com.ar/.env');
        touch($this->dir . '/cliente.com.ar/index.php');
        touch($this->dir . '/archivo.txt');
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->dir);
    }

    /** @test */
    public function lists_only_first_level_directories_sorted(): void
    {
        $dirs = DirectoryListingService::list($this->dir);
        $this->assertSame(['alpha', 'cliente.com.ar', 'zeta.com'], array_column($dirs, 'name'));
    }

    /** @test */
    public function detects_app_installs_and_matches_site_domains(): void
    {
        $dirs = array_column(DirectoryListingService::list($this->dir, ['zeta.com' => 'zeta.com']), null, 'name');
        $this->assertTrue($dirs['zeta.com']['app'], 'carpeta landing/');
        $this->assertTrue($dirs['cliente.com.ar']['app'], '.env + index.php');
        $this->assertFalse($dirs['alpha']['app']);
        $this->assertSame('zeta.com', $dirs['zeta.com']['domain']);
        $this->assertNull($dirs['alpha']['domain']);
    }

    /** @test */
    public function rejects_unsafe_or_invalid_roots(): void
    {
        foreach (['', 'relativa/public_html', '/', $this->dir . '/../' . basename($this->dir), $this->dir . '/no-existe', $this->dir . '/archivo.txt'] as $bad) {
            try {
                DirectoryListingService::resolve($bad);
                $this->fail("Debió rechazar: {$bad}");
            } catch (\InvalidArgumentException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        $this->assertSame(realpath($this->dir), DirectoryListingService::resolve($this->dir . '/'));
    }
}
