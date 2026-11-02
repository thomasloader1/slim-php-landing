@extends('admin/layout')

@section('title', 'Configuración del Shop')
@section('header', 'Configuración del Shop')
@section('subheader', 'Publicá tu catálogo y, si querés, habilitá la tienda para recibir pedidos por WhatsApp.')

@section('content')
@include('admin/partials/section-tabs', ['group' => 'shop'])
@php
    $menuEnabled  = ($settings['menu_enabled'] ?? '0') === '1';
    $showPayment  = ($settings['menu_show_payment'] ?? '0') === '1';
    $shopEnabled  = ($settings['shop_enabled'] ?? '0') === '1';
    $waReady      = \App\Support\WhatsApp::isValidNumber($settings['whatsapp_number'] ?? '');
@endphp

@if($menuEnabled)
<div class="flex justify-end mb-4 gap-3 flex-wrap">
        <a href="{{ url('shop') }}" target="_blank"
           class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Ver página
        </a>
</div>
@endif

@if(!$menuEnabled)
<div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 mb-6 flex items-start gap-3">
    <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 flex-shrink-0"></i>
    <p class="text-amber-300 text-sm">
        La página pública está <strong>desactivada</strong>. <code class="bg-amber-500/20 px-1 rounded">/shop</code> responde 404 hasta que la actives.
    </p>
</div>
@endif

<form action="{{ url('admin/menu/settings') }}" method="POST" class="space-y-6">
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <div class="space-y-6 min-w-0">
            {{-- ── MÓDULO ──────────────────────────────────────────────── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-toggle-on text-amber-400"></i> Módulo
                    </h3>
                </div>
                <div class="p-6 space-y-5">

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white font-bold text-sm">Activar página pública</p>
                            <p class="text-slate-500 text-xs mt-0.5">Hace visible el catálogo en <code class="bg-slate-800 px-1 rounded">/shop</code></p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="menu_enabled" value="1" class="sr-only peer" {{ $menuEnabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <hr class="border-slate-800">

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-white font-bold text-sm flex items-center gap-2"><i class="fa-solid fa-basket-shopping text-amber-400"></i> Habilitar tienda</p>
                            <p class="text-slate-500 text-xs mt-0.5">Agrega carrito: el cliente arma su pedido y lo manda a tu WhatsApp. Los pedidos quedan en <a href="{{ url('admin/shop/orders') }}" class="text-blue-400 hover:underline">Pedidos</a> y reservan stock.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="shop_enabled" value="1" class="sr-only peer" {{ $shopEnabled ? 'checked' : '' }} {{ $waReady ? '' : 'disabled' }}>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 peer-disabled:opacity-40 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                    @if(!$waReady)
                        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 text-xs text-amber-300 flex items-start gap-2">
                            <i class="fa-brands fa-whatsapp mt-0.5"></i>
                            <span>Para habilitar la tienda cargá el número de WhatsApp de la landing en <a href="{{ url('admin/builder') }}" class="underline font-bold">UX Builder → Botón de WhatsApp</a>. Ahí llegan los pedidos.</span>
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Los pedidos llegan al WhatsApp <strong class="text-slate-300">{{ $settings['whatsapp_number'] }}</strong> (se cambia en UX Builder).</p>
                    @endif
                    <div class="flex items-center justify-between gap-4">
                        <label for="shop_max_items" class="text-sm text-slate-300">Máximo de unidades por pedido</label>
                        <input id="shop_max_items" type="number" name="shop_max_items" min="1" max="500" value="{{ $settings['shop_max_items'] ?? 30 }}"
                               class="w-24 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-amber-500">
                    </div>

                    <hr class="border-slate-800">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Nombre del negocio / Marca</label>
                        <input type="text" name="menu_brand_name"
                               value="{{ $settings['menu_brand_name'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: Focaccia & Co">
                        <p class="text-xs text-slate-500 mt-1">Se muestra como título principal en el encabezado de la carta.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Subtítulo del encabezado</label>
                        <input type="text" name="menu_brand_subtitle"
                               value="{{ $settings['menu_brand_subtitle'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: Lista de Precios · Enero 2026">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Texto de cabecera (alternativo)</label>
                        <input type="text" name="menu_header_text"
                               value="{{ $settings['menu_header_text'] ?? 'Nuestra Carta' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Nuestra Carta">
                        <p class="text-xs text-slate-500 mt-1">Se usa si no se configura "Nombre del negocio".</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Texto de pie de página</label>
                        <textarea name="menu_footer_text" rows="2"
                                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                                  placeholder="Ej: Los precios incluyen IVA. Consulte por alérgenos.">{{ $settings['menu_footer_text'] ?? '' }}</textarea>
                    </div>

                </div>
            </div>

            {{-- ── PAGOS ───────────────────────────────────────────────── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-amber-400"></i> Medios de Pago
                    </h3>
                </div>
                <div class="p-6 space-y-5">

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white font-bold text-sm">Mostrar sección de pagos</p>
                            <p class="text-slate-500 text-xs mt-0.5">Aparece al pie del menú con los métodos configurados.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="menu_show_payment" value="1" class="sr-only peer" {{ $showPayment ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Alias de transferencia</label>
                        <input type="text" name="menu_payment_alias"
                               value="{{ $settings['menu_payment_alias'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors font-mono"
                               placeholder="Ej: amamoscocinar">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-3">Métodos aceptados</label>
                        @php
                            $payMethods = array_filter(explode(',', $settings['menu_payment_methods'] ?? 'cash,transfer'));
                        @endphp
                        <div class="flex flex-wrap gap-3">
                            @foreach(['cash' => ['Efectivo','fa-money-bill-wave'], 'transfer' => ['Transferencia','fa-building-columns'], 'qr' => ['QR / Mercado Pago','fa-qrcode'], 'card' => ['Tarjeta','fa-credit-card']] as $val => [$label, $icon])
                            <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-all
                                          {{ in_array($val, $payMethods) ? 'bg-amber-500/10 border-amber-500/40 text-amber-300' : 'border-slate-700 text-slate-400 hover:border-slate-600' }}">
                                <input type="checkbox" name="pay_method_{{ $val }}" value="1"
                                       class="hidden" {{ in_array($val, $payMethods) ? 'checked' : '' }}>
                                <i class="fa-solid {{ $icon }} text-sm"></i>
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        {{-- Campo oculto que se construye con JS --}}
                        <input type="hidden" name="menu_payment_methods" id="payment_methods_val"
                               value="{{ $settings['menu_payment_methods'] ?? 'cash,transfer' }}">
                    </div>

                </div>
            </div>
        </div>
        <div class="space-y-6 min-w-0">
            {{-- ── DISEÑO: vive en el UX Builder + Configuración (mismo design system que la landing) ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-palette text-amber-400"></i> Diseño de la tienda
                    </h3>
                </div>
                <div class="p-6 space-y-4 text-sm text-slate-400">
                    <p>La tienda usa los mismos <strong class="text-slate-200">colores, fondo y logo</strong> que tu landing, así todo el sitio se ve igual.</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ url('admin/builder/shop') }}"
                           class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-all inline-flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Diseñar tienda (banners, carrusel, grilla)
                        </a>
                        <a href="{{ url('admin/settings') }}"
                           class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2.5 rounded-xl text-sm font-bold transition-all inline-flex items-center gap-2 border border-slate-700">
                            <i class="fa-solid fa-droplet"></i> Colores y fondo
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Barra de guardado: queda visible al hacer scroll --}}
    <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3">
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-xl transition-all">
            Guardar Configuración
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
(function () {
    /* ── Checkboxes de métodos de pago → campo oculto ── */
    var payCheckboxes = document.querySelectorAll('[name^="pay_method_"]');
    var payHidden     = document.getElementById('payment_methods_val');

    function updatePayMethods() {
        var selected = [];
        payCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                selected.push(cb.name.replace('pay_method_', ''));
            }
        });
        payHidden.value = selected.join(',');
    }

    payCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            var label = this.closest('label');
            if (this.checked) {
                label.classList.add('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                label.classList.remove('border-slate-700', 'text-slate-400');
            } else {
                label.classList.remove('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                label.classList.add('border-slate-700', 'text-slate-400');
            }
            updatePayMethods();
        });
    });
})();
</script>
@endpush
