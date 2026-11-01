@extends('admin/layout')

@section('title', 'Pedido ' . $order->code)
@section('header', 'Pedido ' . $order->code)
@section('subheader', 'Recibido el ' . $order->created_at?->format('d/m/Y \a \l\a\s H:i'))

@section('content')
@php
    $badge = [
        'pending'  => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'accepted' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
    ];
@endphp

<div class="flex justify-end mb-4">
    <a href="{{ url('admin/shop/orders') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-arrow-left"></i> Volver a Pedidos
    </a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Detalle --}}
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">Detalle</h3>
            <span class="px-3 py-1 text-xs font-bold rounded-full uppercase border {{ $badge[$order->status] ?? '' }}">{{ $order->status_label }}</span>
        </div>
        <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-slate-800/50">
                @foreach($order->items as $item)
                    <tr>
                        <td class="px-4 sm:px-6 py-3 text-slate-400 w-12">{{ $item->quantity }}×</td>
                        <td class="px-2 py-3">
                            <p class="text-white font-bold">{{ $item->display_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ \App\Models\MenuItem::formatMoney($item->unit_price) }} c/u
                                @if($item->stock_reserved) · <span class="text-slate-400">stock reservado</span>@endif
                                @unless($item->menu_item_id) · <span class="text-amber-400">producto eliminado</span>@endunless
                            </p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 text-right text-amber-400 font-bold whitespace-nowrap">{{ \App\Models\MenuItem::formatMoney($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-700">
                    <td colspan="2" class="px-4 sm:px-6 py-4 text-white font-bold text-right">Total</td>
                    <td class="px-4 sm:px-6 py-4 text-right text-amber-400 font-bold text-lg whitespace-nowrap">{{ $order->total_formatted }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Cliente y acciones --}}
    <div class="space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6">
            <h3 class="text-white font-bold mb-3">Cliente</h3>
            <p class="text-white">{{ $order->customer_name }}</p>
            <p class="text-slate-400 text-sm">{{ $order->customer_phone }}</p>
            @if($order->decided_at)
                <p class="text-xs text-slate-500 mt-4">{{ $order->status_label }} por {{ $order->decided_by }} el {{ $order->decided_at->format('d/m H:i') }}</p>
            @endif
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 space-y-3">
            @if($order->isPending())
                <form action="{{ url('admin/shop/orders/' . $order->id . '/accept') }}" method="POST">
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold px-4 py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> Aceptar pedido
                    </button>
                </form>
                <form action="{{ url('admin/shop/orders/' . $order->id . '/reject') }}" method="POST"
                      onsubmit="return confirm('¿Rechazar el pedido {{ $order->code }}? El stock reservado vuelve a estar disponible.')">
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="w-full bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-400 font-bold px-4 py-3 rounded-xl transition-all flex items-center justify-center gap-2 border border-slate-700">
                        <i class="fa-solid fa-xmark"></i> Rechazar
                    </button>
                </form>
                <p class="text-xs text-slate-500">Rechazar devuelve el stock reservado por este pedido.</p>
            @elseif($notifyUrl)
                <a href="{{ $notifyUrl }}" data-wa-web="{{ $notifyWeb }}" target="_blank" rel="noopener"
                   class="w-full bg-green-600 hover:bg-green-500 text-white font-bold px-4 py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Avisar por WhatsApp
                </a>
                <p class="text-xs text-slate-500">Abre un mensaje para {{ $order->customer_name }} diciendo que el pedido fue {{ mb_strtolower($order->status_label) }}. Vos lo enviás.</p>
            @else
                <p class="text-sm text-slate-500">El teléfono del cliente no es válido para WhatsApp.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// En escritorio, abrir WhatsApp Web en vez de la app
(function () {
    var mobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.platform));
    if (!mobile) {
        document.querySelectorAll('a[data-wa-web]').forEach(function (a) { a.href = a.dataset.waWeb; });
    }
})();
</script>
@endpush
@endsection
