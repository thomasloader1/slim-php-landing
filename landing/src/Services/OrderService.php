<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\WhatsApp;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Pedidos del Shop.
 *  - create(): valida, recalcula precios desde la DB y RESERVA stock (todo o nada).
 *  - decide(): pendiente → aceptado (stock queda descontado) o rechazado (se devuelve).
 */
class OrderService
{
    public const MAX_QTY_PER_LINE = 99;
    public const DEFAULT_MAX_ITEMS = 30;

    /**
     * @param array<int,array{id:mixed,variant?:mixed,qty:mixed}> $lines
     * @return array{order: ?Order, errors: array<string,string>} errores por "id:variante" o por campo
     */
    public function create(array $lines, string $name, string $phone, ?string $ip = null): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $phone = trim($phone);
        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Ingresá tu nombre.';
        }
        if (!WhatsApp::isValidNumber($phone) || mb_strlen($phone) > 30) {
            $errors['phone'] = 'Ingresá un teléfono válido (con código de área).';
        }

        // Normaliza y agrupa líneas iguales (mismo producto y variante)
        $wanted = [];
        foreach ($lines as $line) {
            $id = (int) ($line['id'] ?? 0);
            $variant = (int) ($line['variant'] ?? 1);
            $qty = (int) ($line['qty'] ?? 0);
            if ($id <= 0 || $qty <= 0 || !in_array($variant, [1, 2, 3], true)) {
                continue;
            }
            $key = "{$id}:{$variant}";
            $wanted[$key] = min(self::MAX_QTY_PER_LINE, ($wanted[$key] ?? 0) + $qty);
        }
        if (!$wanted) {
            $errors['cart'] = 'El carrito está vacío.';
        }
        $maxItems = $this->maxItems();
        if (array_sum($wanted) > $maxItems) {
            $errors['cart'] = "Podés pedir hasta {$maxItems} unidades por pedido.";
        }
        if ($errors) {
            return ['order' => null, 'errors' => $errors];
        }

        return Capsule::connection()->transaction(function () use ($wanted, $name, $phone, $ip) {
            $ids = array_unique(array_map(fn ($k) => (int) explode(':', $k)[0], array_keys($wanted)));
            $items = MenuItem::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $errors = [];
            $lines = [];
            $neededPerItem = [];
            foreach ($wanted as $key => $qty) {
                [$id, $variant] = array_map('intval', explode(':', $key));
                $item = $items[$id] ?? null;
                if (!$item || !$item->active) {
                    $errors[$key] = 'Este producto ya no está disponible.';
                    continue;
                }
                [$price, $label] = self::variant($item, $variant);
                if ($price === null) {
                    $errors[$key] = "{$item->title}: esa opción ya no está disponible.";
                    continue;
                }
                $neededPerItem[$id] = ($neededPerItem[$id] ?? 0) + $qty;
                $lines[] = compact('item', 'variant', 'qty', 'price', 'label', 'key');
            }

            // Stock: se compara contra lo pedido del producto en todas sus variantes
            foreach ($neededPerItem as $id => $needed) {
                $stock = $items[$id]->stock;
                if ($stock !== null && $stock < $needed) {
                    $msg = $stock <= 0 ? "{$items[$id]->title}: sin stock." : "{$items[$id]->title}: quedan {$stock}.";
                    foreach ($lines as $l) {
                        if ((int) $l['item']->id === $id) {
                            $errors[$l['key']] = $msg;
                        }
                    }
                }
            }
            if ($errors) {
                return ['order' => null, 'errors' => $errors];
            }

            foreach ($neededPerItem as $id => $needed) {
                if ($items[$id]->stock !== null) {
                    MenuItem::whereKey($id)->decrement('stock', $needed);
                }
            }

            $total = array_sum(array_map(fn ($l) => round($l['price'] * $l['qty'], 2), $lines));
            $order = Order::create([
                'code'           => 'TMP-' . bin2hex(random_bytes(6)),
                'customer_name'  => $name,
                'customer_phone' => $phone,
                'status'         => Order::PENDING,
                'total'          => $total,
                'ip_hash'        => $ip ? sha1($ip) : null,
            ]);
            $order->update(['code' => sprintf('P-%06d', $order->id)]);

            foreach ($lines as $l) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'menu_item_id'   => $l['item']->id,
                    'title'          => $l['item']->title,
                    'variant_label'  => $l['label'],
                    'unit_price'     => $l['price'],
                    'quantity'       => $l['qty'],
                    'subtotal'       => round($l['price'] * $l['qty'], 2),
                    'stock_reserved' => $l['item']->stock !== null ? 1 : 0,
                ]);
            }

            return ['order' => $order->load('items'), 'errors' => []];
        });
    }

    /**
     * Acepta o rechaza un pedido pendiente. Rechazar devuelve el stock reservado.
     * @return bool false si el pedido ya no estaba pendiente
     */
    public function decide(Order $order, string $status, string $by): bool
    {
        if (!in_array($status, [Order::ACCEPTED, Order::REJECTED], true)) {
            throw new \InvalidArgumentException("Estado inválido: {$status}");
        }

        return Capsule::connection()->transaction(function () use ($order, $status, $by) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (!$locked || !$locked->isPending()) {
                return false;
            }

            if ($status === Order::REJECTED) {
                foreach ($locked->items as $line) {
                    if (!$line->stock_reserved || !$line->menu_item_id) {
                        continue;
                    }
                    $item = MenuItem::whereKey($line->menu_item_id)->lockForUpdate()->first();
                    if ($item && $item->stock !== null) {
                        $item->increment('stock', $line->quantity);
                    }
                }
            }

            $locked->update(['status' => $status, 'decided_at' => date('Y-m-d H:i:s'), 'decided_by' => mb_substr($by, 0, 100)]);
            $order->setRawAttributes($locked->getAttributes(), true);
            return true;
        });
    }

    /** Comanda que el cliente envía al WhatsApp de la landing. */
    public static function whatsappMessage(Order $order): string
    {
        $lines = ["¡Hola! Quiero hacer este pedido:", "*Pedido {$order->code}*", ''];
        foreach ($order->items as $item) {
            $lines[] = "• {$item->quantity} x {$item->display_name} — " . MenuItem::formatMoney($item->subtotal);
        }
        $lines[] = '';
        $lines[] = '*Total: ' . MenuItem::formatMoney($order->total) . '*';
        $lines[] = '';
        $lines[] = "Nombre: {$order->customer_name}";
        $lines[] = "Teléfono: {$order->customer_phone}";
        return implode("\n", $lines);
    }

    /** Mensaje para avisarle al cliente cómo quedó su pedido. */
    public static function customerNotice(Order $order): string
    {
        return match ($order->status) {
            Order::ACCEPTED => "¡Hola {$order->customer_name}! Tu pedido {$order->code} fue aceptado. Total: "
                . MenuItem::formatMoney($order->total) . '. ¡Gracias por tu compra!',
            Order::REJECTED => "Hola {$order->customer_name}, lamentablemente no podemos tomar tu pedido {$order->code}. "
                . 'Si querés, respondé este mensaje y lo vemos.',
            default => "Hola {$order->customer_name}, recibimos tu pedido {$order->code}. En breve te confirmamos.",
        };
    }

    /** @return array{0: ?float, 1: ?string} [precio, etiqueta] de la variante 1/2/3 */
    public static function variant(MenuItem $item, int $variant): array
    {
        return match ($variant) {
            1 => [(float) $item->price, $item->price_label_1 ?: null],
            2 => $item->price_2 !== null ? [(float) $item->price_2, $item->price_label_2 ?: 'Opción 2'] : [null, null],
            3 => $item->price_3 !== null ? [(float) $item->price_3, $item->price_label_3 ?: 'Opción 3'] : [null, null],
            default => [null, null],
        };
    }

    private function maxItems(): int
    {
        $v = (int) (Capsule::table('settings')->where('setting_key', 'shop_max_items')->value('setting_value') ?? 0);
        return $v > 0 ? $v : self::DEFAULT_MAX_ITEMS;
    }
}
