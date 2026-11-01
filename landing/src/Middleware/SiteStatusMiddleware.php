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
                if (!$this->isValidRedirectUrl($redirectUrl, $request)) {
                    $response = new SlimResponse();
                    return $response->withStatus(403);
                } else {
                    $response = new SlimResponse();
                    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * Validate redirect URL to prevent open redirect attacks.
     * Allows relative URLs (same-origin) and absolute URLs matching the current site's domain.
     */
    private function isValidRedirectUrl(string $redirectUrl, $request): bool
    {
        // Reject protocol-relative URLs (//evil.com) first
        if (str_starts_with($redirectUrl, '//')) {
            return false;
        }

        // Allow relative URLs (same-origin, starting with /)
        if (str_starts_with($redirectUrl, '/')) {
            return true;
        }

        // Parse absolute URL
        $parsed = parse_url($redirectUrl);
        if ($parsed === false) {
            return false;
        }

        // Validate scheme
        $scheme = $parsed['scheme'] ?? '';
        if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        // Validate host matches current site's domain
        $host = $parsed['host'] ?? '';
        if (empty($host)) {
            return false;
        }

        // Get resolved site from request attributes
        $resolvedSite = $request->getAttribute('resolved_site');
        if ($resolvedSite && $resolvedSite->domain) {
            $allowedDomain = strtolower($resolvedSite->domain);
            $redirectDomain = strtolower($host);

            // Remove www. prefix for comparison
            $allowedDomain = preg_replace('/^www\./i', '', $allowedDomain);
            $redirectDomain = preg_replace('/^www\./i', '', $redirectDomain);

            return $redirectDomain === $allowedDomain;
        }

        // If no resolved site, reject absolute URLs for safety
        return false;
    }
}
