<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // Las vistas cargan Tailwind, Font Awesome, Google Fonts, SortableJS desde CDN (Quill se sirve local en /assets)
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com",
            // Solo el propio sitio puede embeber páginas (vista previa del UX Builder)
            "frame-ancestors 'self'",
        ]) . ';';

        return $response
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
