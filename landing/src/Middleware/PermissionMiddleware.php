<?php

namespace App\Middleware;

use App\Services\AccessService;
use App\Support\Permissions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Bloquea las secciones del admin que el usuario no puede ver (por rol o
 * porque el módulo está apagado). Corre después de AuthMiddleware.
 * Rutas no registradas en Permissions::CATALOG se bloquean.
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $path = self::adminPath($request);
        $required = Permissions::forPath($path);
        if ($required === null || ($required !== '' && AccessService::canRequest($required, $request->getMethod(), $path))) {
            return $handler->handle($request);
        }

        log_error(sprintf(
            '[PermissionMiddleware] Acceso denegado a %s (rol=%s, permiso=%s)',
            $request->getUri()->getPath(),
            $_SESSION['user_role'] ?? '-',
            $required === '' ? 'no registrado' : (Permissions::featureFor($required, $request->getMethod(), $path) ?? $required)
        ), 'warning');

        if (self::wantsJson($request)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'No tenés acceso a esta sección.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $_SESSION['flash_error'] = 'No tenés acceso a esa sección.';
        return (new SlimResponse())->withHeader('Location', url('admin'))->withStatus(302);
    }

    /** Path sin APP_BASE_PATH (misma lógica que AuthMiddleware). */
    public static function adminPath(Request $request): string
    {
        $basePath = rtrim(getenv('APP_BASE_PATH') ?: ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        $path = $request->getUri()->getPath();
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        return $path;
    }

    private static function wantsJson(Request $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'application/json')
            || str_contains($request->getHeaderLine('Content-Type'), 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
}
