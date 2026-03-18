<?php

namespace App\Install;

class ModuleRegistry
{
    private string $modulesDir;

    public function __construct()
    {
        // Los módulos viven en db/modules/ relativo a la raíz del proyecto
        $this->modulesDir = __DIR__ . '/../../db/modules';
    }

    /**
     * Descubre todos los módulos disponibles leyendo cada module.json.
     * Retorna array indexado por module id.
     *
     * @return array<string, array{id: string, name: string, description: string, sql: string, required: bool, version: string, sqlPath: string}>
     */
    public function discover(): array
    {
        $modules = [];

        if (!is_dir($this->modulesDir)) {
            return $modules;
        }

        foreach (glob($this->modulesDir . '/*/module.json') as $jsonFile) {
            $data = json_decode(file_get_contents($jsonFile), true);

            if (!$data || empty($data['id'])) {
                continue;
            }

            $moduleDir = dirname($jsonFile);
            $sqlFile   = $data['sql'] ?? 'install.sql';

            $modules[$data['id']] = [
                'id'          => $data['id'],
                'name'        => $data['name'] ?? $data['id'],
                'description' => $data['description'] ?? '',
                'sql'         => $sqlFile,
                'required'    => (bool) ($data['required'] ?? false),
                'version'     => $data['version'] ?? '1.0.0',
                'sqlPath'     => $moduleDir . '/' . $sqlFile,
            ];
        }

        // Ordenar: requeridos primero
        uasort($modules, fn($a, $b) => $b['required'] <=> $a['required']);

        return $modules;
    }
}
