<?php

namespace App\Controllers\Front;

use App\Models\MenuSection;
use App\Models\MenuItem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

class MenuController
{
    protected $view;

    public function __construct(ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();

        // Si el módulo está desactivado retornar 404
        if (($settings['menu_enabled'] ?? '0') !== '1') {
            $response->getBody()->write('Página no disponible.');
            return $response->withStatus(404);
        }

        // Secciones activas con sus items activos
        $sections = MenuSection::active()
            ->with('activeItems')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Items sin sección asignada
        $unsectioned = MenuItem::active()
            ->whereNull('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // URL canónica
        $pageUrl = !empty($settings['seo_site_url'])
            ? rtrim($settings['seo_site_url'], '/') . '/menu'
            : '';

        $html = $this->view->make('menu/index', compact('settings', 'sections', 'unsectioned', 'pageUrl'))->render();
        $response->getBody()->write($html);
        return $response;
    }
}
