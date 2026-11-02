<?php

namespace App\Controllers\Admin;

use App\Models\Order;
use App\Services\OrderService;
use App\Support\WhatsApp;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Pedidos del Shop: lista, detalle, aceptar (el stock ya quedó reservado)
 * y rechazar (devuelve el stock). Permiso "orders".
 */
class OrderAdminController
{
    use AdminViewTrait;

    public const FILTERS = ['pending' => 'Pendientes', 'accepted' => 'Aceptados', 'rejected' => 'Rechazados', 'all' => 'Todos'];

    public function index(Request $request, Response $response): Response
    {
        $filter = (string) ($request->getQueryParams()['status'] ?? 'pending');
        if (!array_key_exists($filter, self::FILTERS)) {
            $filter = 'pending';
        }

        $query = Order::withCount('items')->orderByDesc('id');
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $counts = Order::selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status')->all();

        return $this->render($response, 'admin/shop/orders/index', [
            'orders'  => $query->limit(200)->get(),
            'filter'  => $filter,
            'filters' => self::FILTERS,
            'counts'  => $counts,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $order = Order::with('items')->find($args['id']);
        if (!$order) {
            $_SESSION['flash_error'] = 'El pedido no existe.';
            return $response->withHeader('Location', url('admin/shop/orders'))->withStatus(302);
        }

        // Aviso al cliente: plantilla de Tienda → Configuración, editable antes de enviar
        $notice = OrderService::customerNotice($order);
        $validPhone = WhatsApp::isValidNumber($order->customer_phone);

        return $this->render($response, 'admin/shop/orders/show', [
            'order'       => $order,
            'notice'      => $notice,
            'notifyPhone' => $validPhone ? WhatsApp::normalizeNumber($order->customer_phone) : null,
            'notifyUrl'   => $validPhone ? WhatsApp::appUrl($order->customer_phone, $notice) : null,
            'notifyWeb'   => $validPhone ? WhatsApp::webUrl($order->customer_phone, $notice) : null,
        ]);
    }

    public function accept(Request $request, Response $response, array $args): Response
    {
        return $this->decide($response, (int) $args['id'], Order::ACCEPTED);
    }

    public function reject(Request $request, Response $response, array $args): Response
    {
        return $this->decide($response, (int) $args['id'], Order::REJECTED);
    }

    private function decide(Response $response, int $id, string $status): Response
    {
        $order = Order::find($id);
        $back = url('admin/shop/orders/' . $id);
        if (!$order) {
            $_SESSION['flash_error'] = 'El pedido no existe.';
            return $response->withHeader('Location', url('admin/shop/orders'))->withStatus(302);
        }

        $ok = (new OrderService())->decide($order, $status, (string) ($_SESSION['user_name'] ?? 'admin'));
        if (!$ok) {
            $_SESSION['flash_error'] = "El pedido {$order->code} ya había sido " . mb_strtolower($order->fresh()->status_label) . '.';
        } else {
            $_SESSION['flash_success'] = $status === Order::ACCEPTED
                ? "Pedido {$order->code} aceptado. Avisale al cliente con el botón de WhatsApp."
                : "Pedido {$order->code} rechazado: el stock se devolvió. Avisale al cliente con el botón de WhatsApp.";
        }
        return $response->withHeader('Location', $back)->withStatus(302);
    }
}
