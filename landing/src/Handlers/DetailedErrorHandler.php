<?php

namespace App\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;
use Throwable;

/**
 * Manejador de errores personalizado para Slim.
 * 
 * - Registra logs ultra detallados con toda la información del request,
 *   sesión, stack trace, headers, y entorno.
 * - Muestra una página amigable al usuario (no el dump de Slim).
 * - Soportar displayErrorDetails para entornos de desarrollo.
 */
class DetailedErrorHandler extends ErrorHandler
{
    private $viewFactory;

    public function __construct(
        $callableResolver,
        $responseFactory,
        $viewFactory,
        $logger = null
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
        $this->viewFactory = $viewFactory;
    }

    /**
     * Escribe el log de error con formato ultra detallado
     * directamente a storage/logs/errors-{YYYY-MM-DD}.log.
     */
    protected function writeToErrorLog(): void
    {
        $log = $this->buildLogEntry();
        $this->logError($log); // también al logger de Slim por si hay un monitor externo

        // Log persistente a archivo
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/errors-' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    }

    /**
     * Responde con una página amigable (o detalles si displayErrorDetails=true).
     */
    protected function respond(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->statusCode);

        // Si el cliente espera JSON, devolvemos JSON
        $contentType = $this->determineContentType($this->request);
        if ($contentType === 'application/json') {
            $response = $response->withHeader('Content-Type', 'application/json');
            $payload = json_encode([
                'error'   => true,
                'message' => 'Ha ocurrido un error interno del servidor.',
            ], JSON_UNESCAPED_UNICODE);
            $response->getBody()->write($payload);
            return $response->withStatus(500);
        }

        // Si displayErrorDetails = true, mostramos la info técnica
        if ($this->displayErrorDetails) {
            $response = $response->withHeader('Content-Type', 'text/html');
            $html = $this->renderTechnicalPage();
            $response->getBody()->write($html);
            return $response;
        }

        // Página amigable para el usuario final
        try {
            $html = $this->viewFactory->make('errors.500')->render();
        } catch (Throwable $e) {
            // Si la vista falla, mostramos un HTML inline mínimo
            $html = '<!DOCTYPE html><html><head><title>Error 500</title>';
            $html .= '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#0f0f1a;color:#e2e8f0;text-align:center}</style>';
            $html .= '</head><body><div><h1>500</h1><p>Ha ocurrido un error interno.</p></div></body></html>';
        }

        $response->getBody()->write($html);
        return $response;
    }

    // ─── Private ──────────────────────────────────────────────

    /**
     * Construye una entrada de log ultra detallada.
     */
    private function buildLogEntry(): string
    {
        $e = $this->exception;
        $req = $this->request;
        $ts = date('Y-m-d H:i:s');
        $uri = (string) $req->getUri();
        $method = $req->getMethod();
        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $ua = $req->getHeaderLine('User-Agent') ?: 'unknown';

        $sep = str_repeat('═', 56);
        $lines = [];

        $lines[] = '╔' . $sep . '╗';
        $lines[] = '║  CRITICAL ERROR — ' . $ts . '  ║';
        $lines[] = '╚' . $sep . '╝';
        $lines[] = '';
        $lines[] = 'EXCEPTION:';
        $lines[] = '  Class:   ' . get_class($e);
        $lines[] = '  Code:    ' . $e->getCode();
        $lines[] = '  Message: ' . $e->getMessage();
        $lines[] = '  File:    ' . $e->getFile();
        $lines[] = '  Line:    ' . $e->getLine();
        $lines[] = '';

        $lines[] = 'REQUEST:';
        $lines[] = '  URI:       ' . $uri;
        $lines[] = '  Method:    ' . $method;
        $lines[] = '  IP:        ' . $ip;
        $lines[] = '  User-Agent:' . $ua;
        $lines[] = '  Referer:   ' . ($req->getHeaderLine('Referer') ?: '—');
        $lines[] = '  Query:     ' . ($req->getUri()->getQuery() ?: '—');
        $lines[] = '  Content-Type: ' . ($req->getHeaderLine('Content-Type') ?: '—');
        $lines[] = '';

        // POST body (sanitizado)
        $body = (string) $req->getBody();
        if ($body !== '') {
            // Ocultar campos sensibles comunes
            $sanitized = preg_replace(
                '/(password|pass|contraseña|secret|token|key|api_key)(=|\s*:\s*")[^&\s"]*/i',
                '$1$2[REDACTED]',
                $body
            );
            $lines[] = '  BODY:      ' . $sanitized;
        } else {
            $lines[] = '  BODY:      (empty)';
        }
        $lines[] = '';

        // Headers relevantes
        $lines[] = 'HEADERS:';
        foreach ($req->getHeaders() as $name => $values) {
            $lower = strtolower($name);
            if ($lower === 'cookie' || $lower === 'authorization' || $lower === 'x-csrf-token') {
                $lines[] = '  ' . $name . ': [REDACTED]';
                continue;
            }
            $lines[] = '  ' . $name . ': ' . implode(', ', $values);
        }
        $lines[] = '';

        // Session (solo si existe)
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION)) {
            $lines[] = 'SESSION:';
            foreach ($_SESSION as $key => $value) {
                if (is_scalar($value)) {
                    $lines[] = '  ' . $key . ': ' . $value;
                } else {
                    $lines[] = '  ' . $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
            $lines[] = '';
        }

        // Stack trace
        $lines[] = 'STACK TRACE:';
        $trace = $e->getTraceAsString();
        foreach (explode("\n", $trace) as $line) {
            $lines[] = '  ' . $line;
        }
        $lines[] = '';

        // Previous exception
        $prev = $e->getPrevious();
        while ($prev !== null) {
            $lines[] = 'PREVIOUS EXCEPTION:';
            $lines[] = '  Class:   ' . get_class($prev);
            $lines[] = '  Message: ' . $prev->getMessage();
            $lines[] = '  File:    ' . $prev->getFile();
            $lines[] = '  Line:    ' . $prev->getLine();
            $lines[] = '  Stack Trace:';
            $prevTrace = $prev->getTraceAsString();
            foreach (explode("\n", $prevTrace) as $line) {
                $lines[] = '    ' . $line;
            }
            $lines[] = '';
            $prev = $prev->getPrevious();
        }

        // Server / Environment relevante
        $envKeys = [
            'APP_ENV', 'APP_BASE_PATH', 'APP_DEBUG',
            'DB_HOST', 'DB_NAME', 'DB_PORT',
            'PHP_VERSION', 'SERVER_SOFTWARE', 'DOCUMENT_ROOT',
        ];
        $lines[] = 'SERVER:';
        $server = $req->getServerParams();
        foreach ($envKeys as $key) {
            $val = $server[$key] ?? $_ENV[$key] ?? getenv($key) ?: '—';
            $lines[] = '  ' . $key . ': ' . $val;
        }
        $lines[] = '';
        $lines[] = '╔' . $sep . '╝';
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Renderiza una página técnica con los detalles del error
     * (solo cuando displayErrorDetails=true).
     */
    private function renderTechnicalPage(): string
    {
        $e = $this->exception;
        $req = $this->request;
        $uri = (string) $req->getUri();

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error Interno</title>';
        $html .= '<style>
            body{font-family:system-ui,sans-serif;background:#0f0f1a;color:#e2e8f0;padding:2rem;max-width:900px;margin:0 auto}
            h1{color:#ef4444;border-bottom:2px solid #ef4444;padding-bottom:.5rem}
            .box{background:#1e1e2e;border:1px solid #333;border-radius:8px;padding:1rem;margin:1rem 0;overflow-x:auto}
            .box h3{margin:0 0 .5rem 0;color:#fbbf24}
            .key{color:#60a5fa}
            pre{font-family:monospace;font-size:.85rem;line-height:1.5;white-space:pre-wrap;word-break:break-all}
            .trace{color:#9ca3af;font-size:.8rem}
        </style></head><body>';

        $html .= '<h1>500 — Error Interno del Servidor</h1>';

        $html .= '<div class="box"><h3>🔴 Excepción</h3>';
        $html .= '<pre><span class="key">Class:</span>  ' . htmlspecialchars(get_class($e)) . "\n";
        $html .= '<span class="key">Code:</span>   ' . htmlspecialchars($e->getCode()) . "\n";
        $html .= '<span class="key">Message:</span> ' . htmlspecialchars($e->getMessage()) . "\n";
        $html .= '<span class="key">File:</span>    ' . htmlspecialchars($e->getFile()) . "\n";
        $html .= '<span class="key">Line:</span>    ' . htmlspecialchars($e->getLine()) . '</pre></div>';

        $html .= '<div class="box"><h3>🌐 Request</h3>';
        $html .= '<pre><span class="key">URI:</span>       ' . htmlspecialchars($uri) . "\n";
        $html .= '<span class="key">Method:</span>    ' . htmlspecialchars($req->getMethod()) . "\n";
        $html .= '<span class="key">IP:</span>        ' . htmlspecialchars($req->getServerParams()['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $html .= '<span class="key">User-Agent:</span> ' . htmlspecialchars($req->getHeaderLine('User-Agent')) . '</pre></div>';

        $html .= '<div class="box"><h3>📄 Stack Trace</h3>';
        $html .= '<pre class="trace">' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';

        $prev = $e->getPrevious();
        while ($prev !== null) {
            $html .= '<div class="box"><h3>⏪ Previous Exception: ' . htmlspecialchars(get_class($prev)) . '</h3>';
            $html .= '<pre><span class="key">Message:</span> ' . htmlspecialchars($prev->getMessage()) . "\n";
            $html .= '<span class="key">File:</span>    ' . htmlspecialchars($prev->getFile()) . "\n";
            $html .= '<span class="key">Line:</span>    ' . htmlspecialchars($prev->getLine()) . '</pre>';
            $html .= '<pre class="trace">' . htmlspecialchars($prev->getTraceAsString()) . '</pre></div>';
            $prev = $prev->getPrevious();
        }

        $html .= '</body></html>';
        return $html;
    }
}
