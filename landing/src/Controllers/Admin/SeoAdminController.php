<?php

namespace App\Controllers\Admin;

use App\Models\FaqItem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SeoAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $faqCount = FaqItem::count();

        $html = $this->view->make('admin/seo/index', [
            'settings' => $settings,
            'faqCount' => $faqCount,
        ])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $allowedKeys = [
            'seo_description', 'seo_keywords', 'seo_author',
            'seo_site_url', 'seo_og_image', 'seo_locale', 'seo_twitter_handle',
            'seo_schema_type', 'seo_business_type', 'seo_address', 'seo_noindex',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                );
            }
        }

        return $response->withHeader('Location', url('admin/seo'))->withStatus(302);
    }
}
