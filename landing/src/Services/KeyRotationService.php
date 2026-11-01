<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Re-cifra todos los secretos guardados en la DB master con la clave activa
 * (TENANT_ENCRYPTION_KEY_ACTIVE). Usado por /admin/system y db/scripts/rotate_tenant_key.php.
 */
class KeyRotationService
{
    /** tabla => columnas cifradas */
    private const FIELDS = [
        'sites'   => ['db_pass_encrypted'],
        'servers' => ['key_passphrase_encrypted'],
    ];

    private EncryptionService $encryption;
    private string $activeVersion;

    public function __construct(?EncryptionService $encryption = null, ?string $activeVersion = null)
    {
        $this->activeVersion = $activeVersion
            ?? (getenv('TENANT_ENCRYPTION_KEY_ACTIVE') ?: ($_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] ?? 'v1'));
        $this->encryption = $encryption ?? new EncryptionService(null, null, $this->activeVersion);
    }

    /** Cantidad de valores cifrados con una versión distinta a la activa. */
    public function pendingCount(): int
    {
        return array_sum(array_map('count', $this->collect()));
    }

    /**
     * @return array{rotated:int, errors:string[]}
     */
    public function rotate(bool $dryRun = false): array
    {
        $rotated = 0;
        $errors = [];
        foreach ($this->collect() as $table => $rows) {
            foreach ($rows as [$id, $column, $cipher]) {
                try {
                    $plain = $this->encryption->decrypt($cipher);
                    if (!$dryRun) {
                        Capsule::table($table)->where('id', $id)->update([$column => $this->encryption->encrypt($plain)]);
                    }
                    $rotated++;
                } catch (\Throwable $e) {
                    // Nunca incluir el valor cifrado ni el descifrado en el mensaje
                    $errors[] = "{$table}#{$id}.{$column}: " . $e->getMessage();
                }
            }
        }
        return ['rotated' => $rotated, 'errors' => $errors];
    }

    /** @return array<string, array<int, array{0:int,1:string,2:string}>> */
    private function collect(): array
    {
        $prefix = $this->activeVersion . ':';
        $out = [];
        foreach (self::FIELDS as $table => $columns) {
            if (!Capsule::schema()->hasTable($table)) {
                continue;
            }
            foreach (Capsule::table($table)->get(array_merge(['id'], $columns)) as $row) {
                foreach ($columns as $column) {
                    $value = $row->{$column};
                    if ($value !== null && $value !== '' && !str_starts_with($value, $prefix)) {
                        $out[$table][] = [(int) $row->id, $column, $value];
                    }
                }
            }
        }
        return $out;
    }
}
