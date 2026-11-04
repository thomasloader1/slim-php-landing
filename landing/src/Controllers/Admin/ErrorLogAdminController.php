<?php

namespace App\Controllers\Admin;

use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\ErrorLogService;
use App\Services\SiteConnectionService;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * /admin/system/logs — errores de la master y de todos los sitios cliente
 * en un solo listado. Cada DB guarda los suyos en `error_logs`; acá se leen
 * todas (un sitio que no responde se muestra como aviso, no rompe la vista).
 */
class ErrorLogAdminController
{
    use AdminViewTrait;

    public const LIMIT = 200;

    public function index(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();
        $filters = [
            'level'  => in_array($q['level'] ?? '', ErrorLogService::LEVELS, true) ? $q['level'] : '',
            'source' => preg_match('/^(master|\d+)$/', (string) ($q['source'] ?? '')) ? (string) $q['source'] : '',
            'q'      => mb_substr(trim((string) ($q['q'] ?? '')), 0, 100),
            'from'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($q['from'] ?? '')) ? $q['from'] : '',
        ];

        $sources = $this->sources();
        $rows = [];
        $status = [];
        foreach ($sources as $key => $src) {
            if ($filters['source'] !== '' && $filters['source'] !== (string) $key) {
                continue;
            }
            if ($src['error']) {
                $status[$key] = ['label' => $src['label'], 'error' => $src['error'], 'count' => null];
                continue;
            }
            try {
                $query = $src['conn']->table('error_logs')
                    ->select(['id', 'level', 'message', 'exception_class', 'file', 'line', 'uri', 'method', 'ip', 'created_at']);
                if ($filters['level'] !== '') {
                    $query->where('level', $filters['level']);
                }
                if ($filters['q'] !== '') {
                    $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
                    $query->where(fn ($w) => $w->where('message', 'like', $like)->orWhere('uri', 'like', $like)->orWhere('exception_class', 'like', $like));
                }
                if ($filters['from'] !== '') {
                    $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
                }
                $found = $query->orderByDesc('id')->limit(self::LIMIT)->get();
                foreach ($found as $r) {
                    $r->source = (string) $key;
                    $r->source_label = $src['label'];
                    $rows[] = $r;
                }
                $status[$key] = ['label' => $src['label'], 'error' => null, 'count' => count($found)];
            } catch (\Throwable $e) {
                $status[$key] = ['label' => $src['label'], 'error' => 'Sin tabla error_logs (aplicá migraciones) o DB no disponible.', 'count' => null];
            }
        }

        usort($rows, fn ($a, $b) => strcmp((string) $b->created_at, (string) $a->created_at));
        $truncated = count($rows) > self::LIMIT;
        $rows = array_slice($rows, 0, self::LIMIT);

        return $this->render($response, 'admin/system/logs', [
            'rows'      => $rows,
            'status'    => $status,
            'sources'   => array_map(fn ($s) => $s['label'], $sources),
            'filters'   => $filters,
            'levels'    => ErrorLogService::LEVELS,
            'truncated' => $truncated,
            'retention' => ErrorLogService::RETENTION_DAYS,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $source = (string) ($args['source'] ?? '');
        $sources = $this->sources($source);
        $src = $sources[$source] ?? null;
        $entry = null;
        if ($src && !$src['error']) {
            try {
                $entry = $src['conn']->table('error_logs')->where('id', (int) $args['id'])->first();
            } catch (\Throwable $e) {
                $entry = null;
            }
        }
        if (!$entry) {
            $_SESSION['flash_error'] = 'El registro no existe (¿ya se purgó?).';
            return $response->withHeader('Location', url('admin/system/logs'))->withStatus(302);
        }

        $context = $entry->context ? json_decode($entry->context, true) : null;
        return $this->render($response, 'admin/system/log-entry', [
            'entry'        => $entry,
            'source_label' => $src['label'],
            'trace'        => is_array($context) ? ($context['trace'] ?? null) : null,
            'context'      => is_array($context)
                ? json_encode(array_diff_key($context, ['trace' => 1]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $entry->context,
        ]);
    }

    public function clear(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $only = preg_match('/^(master|\d+)$/', (string) ($body['source'] ?? '')) ? (string) $body['source'] : '';

        $cleared = 0;
        $failed = [];
        foreach ($this->sources($only !== '' ? $only : null) as $key => $src) {
            if ($only !== '' && $only !== (string) $key) {
                continue;
            }
            try {
                if ($src['error']) {
                    throw new \RuntimeException($src['error']);
                }
                $src['conn']->table('error_logs')->delete();
                $cleared++;
            } catch (\Throwable $e) {
                $failed[] = $src['label'];
            }
        }

        if ($failed) {
            $_SESSION['flash_error'] = "Logs vaciados en {$cleared} base(s). Sin acceso: " . implode(', ', $failed) . '.';
        } else {
            $_SESSION['flash_success'] = "Logs vaciados ({$cleared} base/s).";
        }
        return $response->withHeader('Location', url('admin/system/logs'))->withStatus(302);
    }

    /**
     * Bases a leer: master + sitios inicializados.
     * @param string|null $only 'master' o id de sitio para conectar solo esa
     * @return array<string, array{label:string, conn:?Connection, error:?string}>
     */
    protected function sources(?string $only = null): array
    {
        $sources = ['master' => ['label' => 'Master', 'conn' => Capsule::connection('default'), 'error' => null]];
        if ($only === 'master') {
            return $sources;
        }

        try {
            $query = Site::whereNotNull('initialized_at')->orderBy('id');
            if ($only !== null && $only !== '') {
                $query->where('id', (int) $only);
            }
            $sites = $query->get();
        } catch (\Throwable $e) {
            return $sources; // sin tabla sites (instalación sin gestor de sitios)
        }

        $connector = new SiteConnectionService(new EncryptionService());
        foreach ($sites as $site) {
            $label = $site->domain ?: $site->name;
            try {
                $conn = Capsule::connection($connector->connect($site));
                $conn->getPdo();
                $sources[(string) $site->id] = ['label' => $label, 'conn' => $conn, 'error' => null];
            } catch (\Throwable $e) {
                $sources[(string) $site->id] = ['label' => $label, 'conn' => null, 'error' => 'No se pudo conectar a la base del sitio.'];
            }
        }
        return $sources;
    }
}
