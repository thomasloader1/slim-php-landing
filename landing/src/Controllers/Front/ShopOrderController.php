<?php

namespace App\Controllers\Front;

use App\Services\OrderService;
use App\Support\RateLimiter;
use App\Support\WhatsApp;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /shop/orders — pedido público del Shop (JSON).
 *
 * Sin sesión ni CSRF (es público), así que se protege con:
 *  - Content-Type JSON (otro sitio no puede mandarlo sin preflight CORS)
 *  - Origin/Referer del mismo host
 *  - honeypot ("website" debe venir vacío)
 *  - límite de pedidos por IP
 * Los precios y el stock se calculan en el servidor (OrderService).
 */
class ShopOrderController
{
    public const MAX_ORDERS_PER_IP = 5;
    public const WINDOW_SECONDS = 900;

    public function store(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')
            ->whereIn('setting_key', ['menu_enabled', 'shop_enabled', 'whatsapp_number', 'module_menu_enabled', 'module_orders_enabled'])
            ->pluck('setting_value', 'setting_key')->all();

        $open = ($settings['menu_enabled'] ?? '0') === '1'
            && ($settings['shop_enabled'] ?? '0') === '1'
            && ($settings['module_menu_enabled'] ?? '1') === '1'
            && ($settings['module_orders_enabled'] ?? '1') === '1'; // Módulos → Pedidos
        if (!$open) {
            return $this->json($response, 404, ['error' => 'La tienda no está disponible.']);
        }
        $number = (string) ($settings['whatsapp_number'] ?? '');
        if (!WhatsApp::isValidNumber($number)) {
            return $this->json($response, 503, ['error' => 'La tienda no tiene un WhatsApp configurado para recibir pedidos.']);
        }

        if (!str_contains($request->getHeaderLine('Content-Type'), 'application/json') || !self::sameOrigin($request)) {
            return $this->json($response, 403, ['error' => 'Pedido no permitido.']);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, 400, ['error' => 'Pedido inválido.']);
        }
        if (trim((string) ($data['website'] ?? '')) !== '') {
            return $this->json($response, 422, ['error' => 'Pedido inválido.']); // honeypot
        }

        $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');
        if (!RateLimiter::hit('shop-order:' . $ip, self::MAX_ORDERS_PER_IP, self::WINDOW_SECONDS)) {
            return $this->json($response, 429, ['error' => 'Hiciste varios pedidos seguidos. Esperá unos minutos o escribinos por WhatsApp.']);
        }

        $result = (new OrderService())->create(
            is_array($data['items'] ?? null) ? $data['items'] : [],
            (string) ($data['name'] ?? ''),
            (string) ($data['phone'] ?? ''),
            $ip
        );
        if ($result['errors']) {
            return $this->json($response, 422, ['errors' => $result['errors']]);
        }

        $order = $result['order'];
        $message = OrderService::whatsappMessage($order);
        return $this->json($response, 201, [
            'code'    => $order->code,
            'total'   => $order->total_formatted,
            'wa_app'  => WhatsApp::appUrl($number, $message),
            'wa_web'  => WhatsApp::webUrl($number, $message),
        ]);
    }

    /** El pedido tiene que venir de una página de este mismo sitio. */
    public static function sameOrigin(Request $request): bool
    {
        $source = $request->getHeaderLine('Origin') ?: $request->getHeaderLine('Referer');
        if ($source === '') {
            return false;
        }
        $host = strtolower((string) parse_url($source, PHP_URL_HOST));
        $port = parse_url($source, PHP_URL_PORT);
        $uri = $request->getUri();
        return $host !== '' && $host === strtolower($uri->getHost())
            && ($port === null || $uri->getPort() === null || (int) $port === (int) $uri->getPort());
    }

    private function json(Response $response, int $status, array $payload): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
