<?php

namespace App\Controllers\Admin;

use App\Models\Link;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class DashboardController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $linksCount = Link::count();
        $usersCount = User::count();
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();

        $html = $this->view->make('admin/dashboard', [
            'linksCount' => $linksCount,
            'usersCount' => $usersCount,
            'settings' => $settings,
            'user' => $_SESSION
        ])->render();

        $response->getBody()->write($html);
        return $response;
    }
}
