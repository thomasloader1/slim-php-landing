<?php

namespace App\Install;

class InstallLock
{
    private string $lockFile;

    public function __construct()
    {
        $this->lockFile = __DIR__ . '/../../.installed';
    }

    public function isInstalled(): bool
    {
        return file_exists($this->lockFile);
    }

    public function lock(): void
    {
        file_put_contents($this->lockFile, date('Y-m-d H:i:s'));
    }
}
