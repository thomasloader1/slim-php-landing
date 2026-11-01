<?php

namespace App\Controllers\Admin;

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
     */
    public function sso(Request $request, Response $response): Response
    {
        $context = (string) $request->getAttribute('context');
        $site = $request->getAttribute('resolved_site');
        if (!str_starts_with($context, 'site:') || !$site) {
            return $response->withStatus(404);
        }

        $token = (string) ($request->getQueryParams()['token'] ?? '');
        try {
            $siteId = (new \App\Services\SsoTokenService())
                ->consume($token, (int) $site->id, \Illuminate\Database\Capsule\Manager::connection('default'));
        } catch (\Throwable $e) {
            error_log('[AuthController][SSO] ' . $e->getMessage());
            $siteId = null;
        }
        if ($siteId === null) {
            error_log("[AuthController][SECURITY] Token SSO inválido/vencido/reusado para sitio {$site->id}");
            return $this->render($response->withStatus(403), 'admin/login', ['error' => 'Acceso expirado o inválido. Volvé a intentarlo desde el panel master.']);
        }

        // La conexión default ya es la DB del sitio
        $user = \App\Models\User::where('role', 'admin')->where('active', 1)->orderBy('id')->first();
        if (!$user) {
            return $this->render($response, 'admin/login', ['error' => 'El sitio no tiene administradores activos. Inicializalo desde el gestor de sitios.']);
        }

        // Dentro del sitio actúa como root (AccessService::isRoot por impersonated_by)
        $this->auth->loginAs($user, $context, 'superadmin');
        $next = self::safeNext((string) ($request->getQueryParams()['next'] ?? ''));
        error_log("[AuthController][AUDIT] SSO root -> sitio {$site->id} como {$user->email} ({$next})");
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
