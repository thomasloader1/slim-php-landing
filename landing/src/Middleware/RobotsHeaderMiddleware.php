<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Solo la landing (/) y la tienda (/shop) son indexables.
 * Todo lo demás que pase por Slim (admin, login, API, healthz, redirecciones,
 * errores 4xx/5xx, páginas suspendidas) sale con "X-Robots-Tag: noindex, nofollow".
 * Los archivos para crawlers (robots.txt, sitemap.xml, llms.txt) no llevan header:
 * no son páginas y deben poder leerse.
 */
class RobotsHeaderMiddleware implements MiddlewareInterface
{
    public const INDEXABLE_PATHS = ['/', '/shop'];
    public const CRAWLER_FILES   = ['/robots.txt', '/sitemap.xml', '/llms.txt'];

    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        if ($response->hasHeader('X-Robots-Tag')) {
            return $response;
        }

        $path = self::relativePath($request->getUri()->getPath());

        if (in_array($path, self::CRAWLER_FILES, true) && $response->getStatusCode() === 200) {
            // llms.txt se lee pero no debe aparecer como resultado en Google (no afecta a los fetchers IA)
            return $path === '/llms.txt' ? $response->withHeader('X-Robots-Tag', 'noindex') : $response;
        }

        $indexable = in_array($path, self::INDEXABLE_PATHS, true)
            && $request->getMethod() === 'GET'
            && $response->getStatusCode() === 200;

        return $indexable ? $response : $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Path sin APP_BASE_PATH ni "/" final (salvo la raíz). */
    public static function relativePath(string $path): string
    {
        $base = rtrim(url(''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
