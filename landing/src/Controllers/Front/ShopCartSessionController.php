<?php

namespace App\Controllers\Front;

use App\Models\MenuItem;
use App\Services\OrderService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /shop/cart/save — persiste el carrito en la sesión PHP.
 * GET  /shop/cart/session — carga el borrador del carrito desde la sesión.
 *
 * Valida cada ítem contra MenuItem del tenant activo (aislamiento por TenantResolverMiddleware).
 * El payload en sesión incluye tenant_id para aislamiento multitenant correcto.
 */
class ShopCartSessionController
{
    /** Esquema de sesión actual */
    private const SESSION_VERSION = 1;

    /** Asegura que la sesión esté iniciada */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * POST /shop/cart/save
     * Body: { "items": [ { "id": 12, "variant": 1, "qty": 2 }, ... ] }
     */
    public function save(Request $request, Response $response): Response
    {
        $this->ensureSessionStarted();

        // Verificar que el módulo de tienda esté habilitado
        $settings = Capsule::table('settings')
            ->whereIn('setting_key', ['menu_enabled', 'shop_enabled', 'module_menu_enabled', 'module_orders_enabled', 'shop_max_items'])
            ->pluck('setting_value', 'setting_key')->all();

        $open = ($settings['menu_enabled'] ?? '0') === '1'
            && ($settings['shop_enabled'] ?? '0') === '1'
            && ($settings['module_menu_enabled'] ?? '1') === '1'
            && ($settings['module_orders_enabled'] ?? '1') === '1';

        if (!$open) {
            return $this->json($response, 503, ['error' => 'La tienda no está disponible.']);
        }

        // Content-Type JSON y mismo origen (misma protección que ShopOrderController)
        if (!str_contains($request->getHeaderLine('Content-Type'), 'application/json') || !ShopOrderController::sameOrigin($request)) {
            return $this->json($response, 403, ['error' => 'Origen no permitido.']);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, 400, ['error' => 'Payload inválido.']);
        }

        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            return $this->json($response, 400, ['error' => 'Items inválidos.']);
        }

        // Agrupar líneas por id:variant y sumar cantidades (como OrderService::create)
        $wanted = [];
        foreach ($items as $line) {
            $id = (int) ($line['id'] ?? 0);
            $variant = (int) ($line['variant'] ?? 1);
            $qty = (int) ($line['qty'] ?? 0);
            if ($id <= 0 || $qty <= 0 || !in_array($variant, [1, 2, 3], true)) {
                continue;
            }
            $key = "{$id}:{$variant}";
            $wanted[$key] = min(OrderService::MAX_QTY_PER_LINE, ($wanted[$key] ?? 0) + $qty);
        }

        if (!$wanted) {
            // Carrito vacío: limpiar sesión y devolver [] (compatibilidad con test empty_items_clears_session_cart)
            $context = (string) ($request->getAttribute('context') ?? 'master');
            $_SESSION['shop_cart'] = [
                'tenant_id'  => $context,
                'items'      => [],
                'updated_at' => time(),
                'version'    => self::SESSION_VERSION,
            ];
            return $this->json($response, 200, []);
        }

        // Cargar MenuItems del tenant activo (TenantResolverMiddleware ya aisló la DB)
        $ids = array_unique(array_map(fn ($k) => (int) explode(':', $k)[0], array_keys($wanted)));
        $menuItems = MenuItem::whereIn('id', $ids)->where('active', 1)->get()->keyBy('id');

        // Normalizar y validar cada línea agrupada
        $normalized = [];
        $errors = [];
        $neededPerItem = []; // stock total por producto (suma variantes)
        foreach ($wanted as $key => $qty) {
            [$id, $variant] = array_map('intval', explode(':', $key));

            $item = $menuItems[$id] ?? null;
            if (!$item) {
                $errors[$key] = 'Este producto ya no está disponible.';
                continue;
            }

            [$price, $label] = OrderService::variant($item, $variant);
            if ($price === null) {
                $errors[$key] = "{$item->title}: esa opción ya no está disponible.";
                continue;
            }

            // Acumular stock necesitado por producto (todas las variantes)
            $neededPerItem[$id] = ($neededPerItem[$id] ?? 0) + $qty;

            $normalized[] = [
                'id'      => $id,
                'variant' => $variant,
                'title'   => $item->title,
                'label'   => $label,
                'price'   => (float) $price,
                'qty'     => $qty,
                'max'     => $item->stock, // null = sin control
            ];
        }

        // Verificar stock agregado por producto (suma de todas las variantes)
        foreach ($neededPerItem as $id => $needed) {
            $item = $menuItems[$id] ?? null;
            if ($item && $item->stock !== null && $item->stock < $needed) {
                $msg = $item->stock <= 0
                    ? "{$item->title}: sin stock."
                    : "{$item->title}: quedan {$item->stock}.";
                // Marcar error en todas las líneas de este producto
                foreach ($normalized as &$line) {
                    if ($line['id'] === $id) {
                        $errors["{$line['id']}:{$line['variant']}"] = $msg;
                    }
                }
            }
        }

        if ($errors) {
            return $this->json($response, 422, ['errors' => $errors]);
        }

        // Verificar límite total de unidades (setting shop_max_items)
        $maxItems = (int) ($settings['shop_max_items'] ?? OrderService::DEFAULT_MAX_ITEMS);
        $totalUnits = array_sum(array_column($normalized, 'qty'));
        if ($totalUnits > $maxItems) {
            return $this->json($response, 422, ['errors' => ['cart' => "Podés pedir hasta {$maxItems} unidades por pedido."]]);
        }

        // Guardar en sesión PHP con tenant_id para aislamiento multitenant
        $context = (string) ($request->getAttribute('context') ?? 'master');
        $_SESSION['shop_cart'] = [
            'tenant_id'  => $context,
            'items'      => $normalized,
            'updated_at' => time(),
            'version'    => self::SESSION_VERSION,
        ];

        return $this->json($response, 200, [
            'saved'      => true,
            'items_count'=> count($normalized),
            'units'      => $totalUnits,
        ]);
    }

    /**
     * GET /shop/cart/session
     * Returns: { "items": [...], "updated_at": 1234567890 }
     */
    public function session(Request $request, Response $response): Response
    {
        $this->ensureSessionStarted();

        // Verificar que el módulo de tienda esté habilitado
        $settings = Capsule::table('settings')
            ->whereIn('setting_key', ['menu_enabled', 'shop_enabled', 'module_menu_enabled', 'module_orders_enabled'])
            ->pluck('setting_value', 'setting_key')->all();

        $open = ($settings['menu_enabled'] ?? '0') === '1'
            && ($settings['shop_enabled'] ?? '0') === '1'
            && ($settings['module_menu_enabled'] ?? '1') === '1'
            && ($settings['module_orders_enabled'] ?? '1') === '1';

        if (!$open) {
            return $this->json($response, 503, ['error' => 'La tienda no está disponible.']);
        }

        // Mismo origen
        if (!ShopOrderController::sameOrigin($request)) {
            return $this->json($response, 403, ['error' => 'Origen no permitido.']);
        }

        $context = (string) ($request->getAttribute('context') ?? 'master');
        $sessionCart = $_SESSION['shop_cart'] ?? null;

        // Validar tenant_id coincide (aislamiento multitenant)
        if ($sessionCart && isset($sessionCart['tenant_id']) && $sessionCart['tenant_id'] === $context) {
            $items = $sessionCart['items'] ?? [];
            $updatedAt = $sessionCart['updated_at'] ?? 0;
        } else {
            $items = [];
            $updatedAt = 0;
        }

        return $this->json($response, 200, [
            'items'      => $items,
            'updated_at' => $updatedAt,
        ]);
    }

    private function json(Response $response, int $status, array $payload): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
