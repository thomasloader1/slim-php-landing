<?php

namespace App\Controllers\Front;

use App\Models\Link;
use App\Models\Location;
use App\Models\FaqItem;
use App\Entities\LinkEntity;
use App\Services\DemoDataService;
use App\Services\PageLayoutService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;
use Psr\Container\ContainerInterface;

class LandingController
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

        // Settings via Eloquent query builder
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();

        // Links via Eloquent Model
        $linksData = Link::active()->orderBy('sort_order')->get()->toArray();
        if ($demoMode && empty($linksData) && $this->demoDataService) {
            $linksData = $this->demoDataService->getLinks();
        }
        $links = array_map(fn($item) => new LinkEntity($item), $linksData);

        // Locations
        $locations = Location::active()->orderBy('sort_order')->get();
        if ($demoMode && $locations->isEmpty() && $this->demoDataService) {
            $locations = new Collection(
                array_map(fn(array $data) => (new Location())->forceFill($data), $this->demoDataService->getLocations())
            );
        }

        // URL canónica
        $pageUrl = !empty($settings['seo_site_url'])
            ? rtrim($settings['seo_site_url'], '/') . '/'
            : '';

        // FAQ items para Schema.org
        $faqItems = FaqItem::active()->orderBy('sort_order')->get();
        if ($demoMode && $faqItems->isEmpty() && $this->demoDataService) {
            $faqItems = new Collection(
                array_map(fn(array $data) => (new FaqItem())->forceFill($data), $this->demoDataService->getFaqItems())
            );
        }

        // Bloques en el orden del UX Builder (default: orden histórico de la landing)
        $layoutService = new PageLayoutService();
        $blocks = array_values(array_filter(
            $layoutService->decode($settings[PageLayoutService::SETTING_KEY] ?? null),
            fn (array $block) => $layoutService->isRenderable($block, $settings)
        ));

        $html = $this->view->make('index', [
            'blocks'    => $blocks,
            'settings'  => $settings,
            'links'     => $links,
            'locations' => $locations,
            'pageUrl'   => $pageUrl,
            'faqItems'  => $faqItems,
            'demoMode'  => $demoMode,
        ])->render();

        $response->getBody()->write($html);
        return $response;
    }
}
