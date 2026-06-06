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
        // Exempt login/logout routes from auth check so CsrfMiddleware applies to them
        $path = $request->getUri()->getPath();
        if (in_array($path, ['/admin/login', '/admin/logout'], true)) {
            return $handler->handle($request);
        }

        if (!$this->auth->isAuthenticated()) {
            $response = new SlimResponse();
            return $response->withHeader('Location', url('admin/login'))->withStatus(302);
        }

        // Refrescar datos de sesión desde DB en cada request
        $user = \App\Models\User::where('id', $_SESSION['user_id'])->where('active', 1)->first();
        if (!$user) {
            $this->auth->logout();
            $response = new SlimResponse();
            return $response->withHeader('Location', url('admin/login'))->withStatus(302);
        }
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;

        return $handler->handle($request);
    }
}
