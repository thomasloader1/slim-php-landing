<?php

namespace App\Controllers\Admin;

use App\Middleware\TenantResolverMiddleware;
use App\Services\AuthService;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    use AdminViewTrait;

    protected $auth;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->auth = $container->get(AuthService::class);
        $this->view = $container->get('view');
    }

    public function showLogin(Request $request, Response $response): Response
    {
        if ($this->auth->isAuthenticated()) {
            return $response->withHeader('Location', url('admin'))->withStatus(302);
        }

        $params = $request->getQueryParams();
        $error = $params['error'] ?? null;

        return $this->render($response, 'admin/login', $error ? ['error' => $error] : []);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $password = is_string($data['password'] ?? null) ? $data['password'] : '';

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        $context = (string) ($request->getAttribute('context') ?? 'master');

        if ($this->auth->login($email, $password, $ip, $context)) {
            return $response->withHeader('Location', url('admin'))->withStatus(302);
        }

        return $this->render($response, 'admin/login', ['error' => 'Credenciales inválidas']);
    }

    /**
     * GET /admin/sso?token=... en el dominio de un sitio: el superadmin llega desde
     * el gestor de sitios (master) con un token HMAC de un solo uso.
     * La sesión queda atada a la DB master (auth_source): el sitio no necesita
     * tener usuarios propios para que el master entre.
     */
    public function sso(Request $request, Response $response): Response
    {
        $context = (string) $request->getAttribute('context');
        if (!str_starts_with($context, 'site:')) {
            return $response->withStatus(404);
        }
        $site = $request->getAttribute('resolved_site');
        // Modo SITE_ID (copia dedicada): el id viene del .env, no de la tabla sites
        $expectedSiteId = $site ? (int) $site->id : (TenantResolverMiddleware::siteIdFromEnv() ?? 0);
        if ($expectedSiteId <= 0) {
            return $response->withStatus(404);
        }

        $token = (string) ($request->getQueryParams()['token'] ?? '');
        try {
            $siteId = (new \App\Services\SsoTokenService())
                ->consume($token, $expectedSiteId, \Illuminate\Database\Capsule\Manager::connection('master'));
        } catch (\Throwable $e) {
            log_error('[AuthController][SSO] ' . $e->getMessage(), null, [], $e);
            $siteId = null;
        }
        if ($siteId === null) {
            log_error("[AuthController][SECURITY] Token SSO inválido/vencido/reusado para sitio {$expectedSiteId}");
            return $this->render($response->withStatus(403), 'admin/login', ['error' => 'Acceso expirado o inválido. Volvé a intentarlo desde el panel master.']);
        }

        // Root de la master (no un usuario de la DB del sitio)
        $masterUser = $this->auth->masterRoot();
        if (!$masterUser) {
            return $this->render($response, 'admin/login', ['error' => 'La master no tiene un administrador root activo. Crealo desde la master y volvé a intentar.']);
        }

        $user = new \App\Models\User();
        $user->setRawAttributes((array) $masterUser);
        $user->exists = true;

        // Dentro del sitio actúa como root (AccessService::isRoot por impersonated_by)
        $this->auth->loginAs($user, $context, 'superadmin');
        $_SESSION['auth_source'] = 'master';
        $next = self::safeNext((string) ($request->getQueryParams()['next'] ?? ''));
        log_error("[AuthController][AUDIT] SSO root -> sitio {$siteId} como {$masterUser->email} ({$next})");
        return $response->withHeader('Location', url($next))->withStatus(302);
    }

    /**
     * Destino interno tras el SSO: solo paths del admin ("admin/roles").
     * Cualquier otra cosa (URLs externas, //host, ..) vuelve al dashboard.
     */
    public static function safeNext(string $next): string
    {
        $next = trim($next, '/');
        return preg_match('#^admin(/[a-z0-9_-]+)*$#', $next) ? $next : 'admin';
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->withHeader('Location', url('admin/login'))->withStatus(302);
    }
}
