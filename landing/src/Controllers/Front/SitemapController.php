<?php

namespace App\Controllers\Front;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Genera sitemap.xml y robots.txt dinámicamente usando la configuración del sitio.
 */
class SitemapController
{
    public function sitemap(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $siteUrl  = rtrim($settings['seo_site_url'] ?? '', '/');

        if (empty($siteUrl)) {
            $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $siteUrl = $proto . '://' . $host;
        }

        // Obtener última modificación de settings
        $lastmod = Capsule::table('settings')->max('updated_at');
        $lastmod = $lastmod ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Página principal
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($siteUrl . '/') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
        $xml .= '    <changefreq>weekly</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";

        // Página de menú (solo si está habilitada)
        $menuEnabled = ($settings['menu_enabled'] ?? '0') === '1';
        if ($menuEnabled) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($siteUrl . '/menu') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }

    public function robots(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $siteUrl  = rtrim($settings['seo_site_url'] ?? '', '/');

        $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $siteUrl ?: ($proto . '://' . $host);

        $txt  = "User-agent: *\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /api/\n";
        $txt .= "Allow: /\n";
        $txt .= "\n";
        $txt .= "Sitemap: " . rtrim($baseUrl, '/') . "/sitemap.xml\n";

        $response->getBody()->write($txt);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }
}
