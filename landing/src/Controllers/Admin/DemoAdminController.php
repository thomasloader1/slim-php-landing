<?php

namespace App\Controllers\Admin;

use App\Services\DemoDataService;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class DemoAdminController
{
    use AdminViewTrait;

    private DemoDataService $demoDataService;

    public function __construct(
        \Psr\Container\ContainerInterface $container,
        DemoDataService $demoDataService
    ) {
        $this->view = $container->get('view');
        $this->demoDataService = $demoDataService;
    }

    public function reset(Request $request, Response $response): Response
    {
        try {
            Capsule::transaction(function () {
                // Vaciar tablas con DELETE: TRUNCATE en MySQL hace commit implícito y
                // rompe la transacción ("There is no active transaction" -> siempre 500)
                Capsule::table('menu_items')->delete();
                Capsule::table('menu_sections')->delete();
                Capsule::table('links')->delete();
                Capsule::table('locations')->delete();
                Capsule::table('faq_items')->delete();

                // Re-insert demo data
                $links = $this->demoDataService->getLinks();
                if (!empty($links)) {
                    foreach ($links as &$link) {
                        unset($link['id']); // Remove ID so auto-increment assigns new ones
                    }
                    unset($link);
                    Capsule::table('links')->insert($links);
                }

                $locations = $this->demoDataService->getLocations();
                if (!empty($locations)) {
                    foreach ($locations as &$loc) {
                        unset($loc['id']);
                    }
                    unset($loc);
                    Capsule::table('locations')->insert($locations);
                }

                $sections = $this->demoDataService->getMenuSections();
                if (!empty($sections)) {
                    foreach ($sections as &$section) {
                        unset($section['id']);
                    }
                    unset($section);
                    Capsule::table('menu_sections')->insert($sections);
                }

                $items = $this->demoDataService->getMenuItems();
                if (!empty($items)) {
                    foreach ($items as &$item) {
                        unset($item['id']);
                    }
                    unset($item);
                    Capsule::table('menu_items')->insert($items);
                }

                $faq = $this->demoDataService->getFaqItems();
                if (!empty($faq)) {
                    foreach ($faq as &$f) {
                        unset($f['id']);
                    }
                    unset($f);
                    Capsule::table('faq_items')->insert($faq);
                }
            });

            return $response
                ->withHeader('Location', url('admin') . '?success=' . urlencode('Datos de demo restaurados correctamente.'))
                ->withStatus(302);
        } catch (\Throwable $e) {
            $response->getBody()->write(
                json_encode(['error' => 'Error al restaurar datos de demo: ' . $e->getMessage()])
            );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
