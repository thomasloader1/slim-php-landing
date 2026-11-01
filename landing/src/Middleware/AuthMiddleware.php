<?php

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    protected $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $basePath = rtrim(getenv('APP_BASE_PATH') ?: ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        $path = $request->getUri()->getPath();
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if (in_array($path, ['/admin/login', '/admin/logout', '/admin/sso'], true)) {
            return $handler->handle($request);
        }

        if (!$this->auth->isAuthenticated()) {
            return $this->redirectToLogin();
        }

        // La sesión solo vale en el contexto donde se autenticó (master o site:<id>).
        // Sesiones previas a este cambio no tienen contexto: se asumen master.
        $requestContext = (string) ($request->getAttribute('context') ?? 'master');
        $sessionContext = $this->auth->sessionContext() ?? 'master';
        if ($sessionContext !== $requestContext) {
            error_log("[AuthMiddleware][SECURITY] Sesión de '{$sessionContext}' usada en '{$requestContext}'. Logout forzado.");
            $this->auth->logout();
            return $this->redirectToLogin();
        }

        // La conexión default ya apunta a la DB del contexto (TenantResolverMiddleware)
        $user = \App\Models\User::query()
            ->where('id', $_SESSION['user_id'])
            ->where('active', 1)
            ->first();
        if (!$user) {
            $this->auth->logout();
            return $this->redirectToLogin();
        }
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;

        return $handler->handle($request);
    }

    private function redirectToLogin(): Response
    {
        return (new SlimResponse())->withHeader('Location', url('admin/login'))->withStatus(302);
    }
}
