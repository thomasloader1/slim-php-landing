<?php

namespace App\Controllers\Admin;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    protected $auth;
    protected $view;

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

        $html = $this->view->make('admin/login')->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if ($this->auth->login($email, $password)) {
            return $response->withHeader('Location', url('admin'))->withStatus(302);
        }

        $html = $this->view->make('admin/login', ['error' => 'Credenciales inválidas'])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->withHeader('Location', url('admin/login'))->withStatus(302);
    }
}
