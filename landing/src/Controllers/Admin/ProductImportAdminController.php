<?php

namespace App\Controllers\Admin;

use App\Models\MenuItem;
use App\Services\ProductCsvService;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Importación / exportación masiva de productos por CSV (abre en Excel).
 * Flujo: plantilla → completar → subir → vista previa → confirmar.
 * La vista previa queda en sesión hasta confirmar (atada a la DB del sitio).
 */
class ProductImportAdminController
{
    use AdminViewTrait;

    private const SESSION_KEY = 'product_import';

    public function template(Request $request, Response $response): Response
    {
        return $this->download($response, (new ProductCsvService())->template(), 'plantilla-productos.csv');
    }

    public function export(Request $request, Response $response): Response
    {
        $items = MenuItem::with('section')->orderBy('sort_order')->orderBy('id')->get();
        return $this->download($response, (new ProductCsvService())->export($items), 'productos-' . date('Y-m-d') . '.csv');
    }

    public function form(Request $request, Response $response): Response
    {
        unset($_SESSION[self::SESSION_KEY]);
        return $this->render($response, 'admin/menu/import', ['preview' => null]);
    }

    public function preview(Request $request, Response $response): Response
    {
        $file = $request->getUploadedFiles()['csv_file'] ?? null;

        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->fail($response, 'Elegí un archivo CSV para importar.');
        }
        if ($file->getError() !== UPLOAD_ERR_OK || $file->getSize() > ProductCsvService::MAX_BYTES) {
            return $this->fail($response, 'No se pudo leer el archivo. El máximo es 2 MB.');
        }
        $ext = strtolower(pathinfo((string) $file->getClientFilename(), PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xls', 'ods'], true)) {
            return $this->fail($response, 'Ese archivo es un libro de Excel. En Excel usá Archivo → Guardar como → "CSV UTF-8 (delimitado por comas)" y subí ese archivo.');
        }
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return $this->fail($response, 'El archivo debe ser .csv.');
        }

        $service = new ProductCsvService();
        $parsed = $service->parse((string) $file->getStream());

        if ($parsed['errors'] && !$parsed['rows']) {
            return $this->fail($response, implode(' ', $parsed['errors']));
        }

        $parsed = $service->plan($parsed);

        $valid = [];
        $counts = ['create' => 0, 'update' => 0, 'error' => 0];
        foreach ($parsed['rows'] as $row) {
            $counts[$row['action']]++;
            if ($row['action'] !== 'error') {
                $valid[] = $row['data'];
            }
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY] = [
            'token'    => $token,
            'database' => Capsule::connection()->getDatabaseName(),
            'columns'  => $parsed['columns'],
            'rows'     => $valid,
        ];

        return $this->render($response, 'admin/menu/import', [
            'preview' => [
                'token'    => $token,
                'filename' => (string) $file->getClientFilename(),
                'columns'  => $parsed['columns'],
                'rows'     => $parsed['rows'],
                'errors'   => $parsed['errors'],
                'counts'   => $counts,
                'new_categories' => $parsed['new_categories'],
            ],
        ]);
    }

    public function confirm(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $pending = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);

        if (
            !$pending
            || !hash_equals($pending['token'], (string) ($data['import_token'] ?? ''))
            || $pending['database'] !== Capsule::connection()->getDatabaseName()
        ) {
            return $this->fail($response, 'La vista previa expiró. Volvé a subir el archivo.');
        }
        if (!$pending['rows']) {
            return $this->fail($response, 'No hay filas válidas para importar.');
        }

        try {
            $stats = (new ProductCsvService())->apply($pending['columns'], $pending['rows']);
        } catch (\Throwable $e) {
            log_error('[ProductImport] ' . $e->getMessage(), null, [], $e);
            return $this->fail($response, 'La importación falló y no se guardó ningún cambio. Revisá el archivo e intentá de nuevo.');
        }

        $msg = "Importación lista: {$stats['created']} productos creados, {$stats['updated']} actualizados";
        if ($stats['categories']) {
            $msg .= $stats['categories'] === 1 ? ', 1 categoría nueva' : ", {$stats['categories']} categorías nuevas";
        }
        $_SESSION['flash_success'] = $msg . '.';

        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
    }

    private function download(Response $response, string $csv, string $filename): Response
    {
        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store');
    }

    private function fail(Response $response, string $message): Response
    {
        $_SESSION['flash_error'] = $message;
        return $response->withHeader('Location', url('admin/menu/items/import'))->withStatus(302);
    }
}
