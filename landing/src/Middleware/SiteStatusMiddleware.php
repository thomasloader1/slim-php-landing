<?php

namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class SiteStatusMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Verificar si existe el setting site_status (módulo site_client instalado)
        $settings = Capsule::table('settings')
            ->whereIn('setting_key', ['site_status', 'site_redirect_url'])
            ->pluck('setting_value', 'setting_key')->toArray();

        $status = $settings['site_status'] ?? 'active';

        if ($status === 'suspended') {
            $redirectUrl = $settings['site_redirect_url'] ?? '';
            $path = $request->getUri()->getPath();
            $basePath = rtrim(getenv('APP_BASE_PATH') ?: ($_ENV['APP_BASE_PATH'] ?? ''), '/');

            // Admin sigue accesible (el admin del cliente ve banner de suspensión)
            $adminPath = $basePath . '/admin';
            if (str_starts_with($path, $adminPath)) {
                $request = $request->withAttribute('site_suspended', true);
                return $handler->handle($request);
            }

            // Todo tráfico público redirige
            if (!empty($redirectUrl)) {
                $response = new SlimResponse();
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        }

        return $handler->handle($request);
    }
}
