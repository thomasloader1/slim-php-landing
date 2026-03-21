<?php

namespace App\Controllers\Front;

use App\Models\Link;
use App\Models\Location;
use App\Models\FaqItem;
use App\Entities\LinkEntity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

class LandingController
{
    protected $view;

    public function __construct(ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // Settings via Eloquent query builder
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();

        // Links via Eloquent Model
        $linksData = Link::active()->orderBy('sort_order')->get()->toArray();
        
        $links = array_map(fn($item) => new LinkEntity($item), $linksData);

        // Locations
        $locations = Location::active()->orderBy('sort_order')->get();

        // URL canónica
        $pageUrl = !empty($settings['seo_site_url'])
            ? rtrim($settings['seo_site_url'], '/') . '/'
            : '';

        // FAQ items para Schema.org
        $faqItems = FaqItem::active()->orderBy('sort_order')->get();

        $html = $this->view->make('index', [
            'settings'  => $settings,
            'links'     => $links,
            'locations' => $locations,
            'pageUrl'   => $pageUrl,
            'faqItems'  => $faqItems,
        ])->render();

        $response->getBody()->write($html);
        return $response;
    }
}
