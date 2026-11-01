<?php

namespace App\Controllers\Front;

use App\Models\MenuSection;
use App\Models\MenuItem;
use App\Services\DemoDataService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;
use Psr\Container\ContainerInterface;

class MenuController
{
    protected $view;

    protected ?DemoDataService $demoDataService;

    public function __construct(ContainerInterface $container, ?DemoDataService $demoDataService = null)
    {
        $this->view = $container->get('view');
        $this->demoDataService = $demoDataService;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $demoMode = (bool) $request->getAttribute('demo_mode', false);

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

        if ($demoMode && $sections->isEmpty() && $this->demoDataService) {
            $demoSections = $this->demoDataService->getMenuSections();
            $demoItems = $this->demoDataService->getMenuItems();

            $sectionModels = new Collection();
            foreach ($demoSections as $sectionData) {
                $section = (new MenuSection())->forceFill($sectionData);
                // Attach matching items as the activeItems relationship
                $childItems = array_filter($demoItems, fn(array $i) => ($i['section_id'] ?? null) === ($sectionData['id'] ?? null));
                $section->setRelation('activeItems', new Collection(
                    array_map(fn(array $data) => (new MenuItem())->forceFill($data), $childItems)
                ));
                $sectionModels->push($section);
            }
            $sections = $sectionModels;
        }

        // Items sin sección asignada
        $unsectioned = MenuItem::active()
            ->whereNull('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($demoMode && $unsectioned->isEmpty() && $this->demoDataService) {
            $demoItems = $this->demoDataService->getMenuItems();
            $demoSections = $this->demoDataService->getMenuSections();
            $sectionIds = array_column($demoSections, 'id');

            $unsecItems = array_filter($demoItems, fn(array $i) => !in_array($i['section_id'] ?? null, $sectionIds, true));
            $unsectioned = new Collection(
                array_map(fn(array $data) => (new MenuItem())->forceFill($data), $unsecItems)
            );
        }

        // URL canónica
        $pageUrl = !empty($settings['seo_site_url'])
            ? rtrim($settings['seo_site_url'], '/') . '/menu'
            : '';

        $html = $this->view->make('menu/index', compact('settings', 'sections', 'unsectioned', 'pageUrl', 'demoMode'))->render();
        $response->getBody()->write($html);
        return $response;
    }
}
