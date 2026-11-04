<?php

namespace App\Controllers\Front;

use App\Models\MenuSection;
use App\Models\MenuItem;
use App\Services\DemoDataService;
use App\Services\PageLayoutService;
use App\Services\StructuredDataService;
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

        // Página sin publicar o módulo Catálogo apagado en Módulos → 404
        if (!shop_is_public($settings)) {
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

        // Contenido demo nunca se indexa (datos ficticios)
        if ($demoMode) {
            $settings['seo_noindex'] = '1';
        }

        // URL canónica absoluta + JSON-LD (CollectionPage, BreadcrumbList, ItemList de Product)
        $pageUrl = site_base_url($settings) . '/shop';
        $seo = new StructuredDataService($settings);
        $jsonLd = [$seo->shopGraph(
            $sections,
            $unsectioned,
            array_map(fn ($l) => new \App\Entities\LinkEntity($l), \App\Models\Link::active()->orderBy('sort_order')->get()->toArray()),
            \App\Models\Location::active()->orderBy('sort_order')->get()
        )];
        $pageDescription = $seo->shopDescription($sections, $unsectioned);
        $shopTitle = $seo->shopTitle();

        // Modo tienda: carrito + pedidos por WhatsApp (necesita un número válido en UX Builder)
        $shopOpen = ($settings['shop_enabled'] ?? '0') === '1'
            && ($settings['module_orders_enabled'] ?? '1') === '1' // Módulos → Pedidos apagado: solo catálogo
            && \App\Support\WhatsApp::isValidNumber($settings['whatsapp_number'] ?? '');
        $waMode = in_array($settings['whatsapp_open_mode'] ?? 'auto', \App\Support\WhatsApp::OPEN_MODES, true)
            ? $settings['whatsapp_open_mode'] ?? 'auto'
            : 'auto';

        // Contexto de tenant para la clave localStorage del carrito
        $tenantContext = (string) ($request->getAttribute('context') ?? 'master');

        // Bloques en el orden del UX Builder → Tienda (default: encabezado, productos, pagos)
        $layoutService = new PageLayoutService('shop');
        $blocks = array_values(array_filter(
            $layoutService->decode($settings[$layoutService->settingKey()] ?? null),
            fn (array $block) => $layoutService->isRenderable($block, $settings)
        ));

        $html = $this->view->make('menu/index', compact('settings', 'sections', 'unsectioned', 'pageUrl', 'demoMode', 'shopOpen', 'waMode', 'blocks', 'jsonLd', 'pageDescription', 'shopTitle', 'tenantContext'))->render();
        $response->getBody()->write($html);
        return $response;
    }
}
