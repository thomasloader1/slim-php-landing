<?php

namespace App\Controllers\Admin;

use App\Models\FaqItem;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SeoAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $faqCount = FaqItem::count();

        return $this->render($response, 'admin/seo/index', [
            'settings' => $settings,
            'faqCount' => $faqCount,
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $allowedKeys = [
            'seo_description', 'seo_keywords', 'seo_author',
            'seo_site_url', 'seo_og_image', 'seo_locale', 'seo_twitter_handle',
            'seo_schema_type', 'seo_business_type', 'seo_address', 'seo_noindex',
            'seo_shop_description', 'seo_currency',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys, true) && is_scalar($value)) {
                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => self::normalize($key, (string) $value), 'updated_at' => date('Y-m-d H:i:s')]
                );
            }
        }

        $_SESSION['flash_success'] = 'SEO guardado.';
        return $response->withHeader('Location', url('admin/seo'))->withStatus(302);
    }

    /**
     * Normaliza valores SEO antes de guardarlos: URLs absolutas válidas, tipos schema.org
     * con formato correcto y moneda ISO. Lo inválido se guarda vacío (usa el fallback).
     */
    public static function normalize(string $key, string $value): string
    {
        $value = trim($value);
        switch ($key) {
            case 'seo_site_url':
                if ($value === '') {
                    return '';
                }
                if (!preg_match('#^https?://#i', $value)) {
                    $value = 'https://' . $value;
                }
                return filter_var($value, FILTER_VALIDATE_URL) ? rtrim($value, '/') : '';
            case 'seo_business_type':
                return preg_match('/^[A-Z][A-Za-z]{2,60}$/', $value) ? $value : '';
            case 'seo_schema_type':
                return in_array($value, ['Person', 'Organization', 'LocalBusiness'], true) ? $value : 'Person';
            case 'seo_currency':
                return in_array($value, ['ARS', 'USD'], true) ? $value : 'ARS';
            case 'seo_noindex':
                return $value === '1' ? '1' : '0';
            case 'seo_twitter_handle':
                return ltrim($value, '@');
            case 'seo_description':
            case 'seo_shop_description':
                return mb_substr(preg_replace('/\s+/u', ' ', $value), 0, 300);
        }
        return mb_substr($value, 0, 500);
    }
}
