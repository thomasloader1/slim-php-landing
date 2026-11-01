@extends('admin/layout')

@section('title', 'Productos')
@section('header', 'Productos')
@section('subheader', 'Administrá tu catálogo: precios, fotos y stock. Arrastrá para reordenar.')

@section('content')
@php
    $__shopSettings = \Illuminate\Database\Capsule\Manager::table('settings')
        ->whereIn('setting_key', ['menu_enabled', 'shop_enabled', 'whatsapp_number'])->pluck('setting_value', 'setting_key')->all();
    $__shopOn = ($__shopSettings['menu_enabled'] ?? '0') === '1' && ($__shopSettings['shop_enabled'] ?? '0') === '1'
        && \App\Support\WhatsApp::isValidNumber($__shopSettings['whatsapp_number'] ?? '');
@endphp
@if($__shopOn)
    <div class="mb-4 flex flex-wrap items-center gap-3 bg-green-500/10 border border-green-500/20 rounded-2xl p-4">
        <i class="fa-solid fa-store text-green-400"></i>
        <p class="flex-1 text-sm text-green-300">La tienda está <strong>abierta</strong>: los clientes eligen productos en /shop y el pedido te llega por WhatsApp.</p>
        <a href="{{ url('shop') }}" target="_blank" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Ver tienda
        </a>
    </div>
@else
    <div class="mb-4 flex flex-wrap items-center gap-3 bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4">
        <i class="fa-solid fa-store-slash text-amber-400"></i>
        <p class="flex-1 text-sm text-amber-200">La tienda está <strong>cerrada</strong>: en /shop los clientes solo ven el catálogo, sin poder hacer pedidos.</p>
        <form action="{{ url('admin/menu/settings/enable-shop') }}" method="POST">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                <i class="fa-solid fa-basket-shopping"></i> Habilitar tienda
            </button>
        </form>
    </div>
@endif
<div class="flex flex-wrap justify-end mb-4 gap-3">
    <a href="{{ url('admin/menu/items/import') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-file-import"></i> Importar Excel
    </a>
    <a href="{{ url('admin/menu/items/export') }}" data-no-loading class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-file-export"></i> Exportar
    </a>
    <a href="{{ url('admin/menu/sections') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-layer-group"></i> Categorías
    </a>
    <a href="{{ url('admin/menu/settings') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-gear"></i> Configuración
    </a>
</div>

@if(count($items) > 0)
{{-- ── Actualizar precios por porcentaje ── --}}
<form id="price-form" action="{{ url('admin/menu/items/adjust-prices') }}" method="POST" data-no-loading
      class="bg-slate-900 border border-slate-800 rounded-3xl p-4 sm:p-5 mb-6">
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-percent text-amber-400"></i>
        <h3 class="text-white font-bold">Actualizar precios</h3>
        <span class="text-xs text-slate-500 hidden sm:inline">Positivo aumenta, negativo baja.</span>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <div class="col-span-1 lg:col-span-2">
            <label for="pf-percent" class="block text-xs text-slate-400 mb-1">Porcentaje</label>
            <div class="relative">
                <input id="pf-percent" type="number" name="percent" step="0.01" min="-90" max="1000" required placeholder="10"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-3 pr-8 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">%</span>
            </div>
        </div>
        <div class="col-span-1 lg:col-span-3">
            <label for="pf-scope" class="block text-xs text-slate-400 mb-1">Aplicar a</label>
            <select id="pf-scope" name="scope" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500">
                <option value="all">Todos los productos ({{ count($items) }})</option>
                <option value="section">Una categoría…</option>
                <option value="selected">Productos tildados (0)</option>
            </select>
        </div>
        <div class="col-span-2 lg:col-span-3 hidden" id="pf-section-wrap">
            <label for="pf-section" class="block text-xs text-slate-400 mb-1">Categoría</label>
            <select id="pf-section" name="section_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500">
                @foreach($sections as $section)
                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-1 lg:col-span-2">
            <label for="pf-round" class="block text-xs text-slate-400 mb-1">Redondeo</label>
            <select id="pf-round" name="round_to" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500">
                <option value="0">Sin redondeo</option>
                <option value="1">A $1</option>
                <option value="10">A $10</option>
                <option value="100">A $100</option>
            </select>
        </div>
        <label class="col-span-1 lg:col-span-2 flex items-center gap-2 text-xs text-slate-400 cursor-pointer pb-3">
            <input type="checkbox" name="include_alt" value="1" checked class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-amber-500 focus:ring-0">
            Incluir precios 2 y 3
        </label>
        <div class="col-span-2 lg:col-span-12 flex justify-end">
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-5 py-2.5 rounded-xl text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Aplicar
            </button>
        </div>
    </div>
</form>
@endif

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <h3 class="text-lg font-bold text-white">Lista de Productos</h3>
        <a href="{{ url('admin/menu/items/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-4 md:py-4 font-bold w-16">
                        <input type="checkbox" id="select-all" aria-label="Tildar todos los productos"
                               class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-amber-500 focus:ring-0 ml-6">
                    </th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-16 hidden sm:table-cell">Foto</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Nombre</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">Categoría</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Precio</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Stock</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-items" class="divide-y divide-slate-800/50">
                @if(count($items) > 0)
                    @foreach($items as $item)
                        <tr class="hover:bg-slate-800/20 transition-colors" data-id="{{ $item->id }}" data-section="{{ $item->section_id }}">
                            <td class="px-3 py-3 md:px-4 md:py-4">
                                <div class="flex items-center gap-3">
                                    <span class="drag-handle cursor-grab text-slate-600 hover:text-slate-400 transition-colors">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    </span>
                                    <input type="checkbox" name="ids[]" value="{{ $item->id }}" form="price-form" aria-label="Tildar {{ $item->title }}"
                                           class="item-check w-4 h-4 rounded border-slate-600 bg-slate-800 text-amber-500 focus:ring-0">
                                </div>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
                                         class="w-12 h-12 rounded-lg object-cover border border-slate-700">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center">
                                        <i class="fa-solid fa-utensils text-slate-600 text-sm"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4">
                                <p class="text-white font-bold">{{ $item->title }}</p>
                                @if($item->sku)
                                    <p class="text-slate-500 text-[10px] font-mono">{{ $item->sku }}</p>
                                @endif
                                @if($item->description)
                                    <p class="text-slate-500 text-xs truncate max-w-[180px]">{{ $item->description }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden md:table-cell">
                                @if($item->section)
                                    <span class="px-2 py-1 bg-blue-500/10 text-blue-400 text-[10px] font-bold rounded-full border border-blue-500/10">{{ $item->section->name }}</span>
                                @else
                                    <span class="text-slate-600 text-xs italic">Sin categoría</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                <span class="text-amber-400 font-bold text-sm">{{ $item->price_formatted }}</span>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                @if($item->stock === null)
                                    <span class="text-slate-600 text-xs">—</span>
                                @elseif($item->is_out_of_stock)
                                    <span class="px-2 py-1 bg-red-500/10 text-red-400 text-[10px] font-bold rounded-full uppercase border border-red-500/10">Sin stock</span>
                                @else
                                    <span class="text-slate-300 text-sm font-bold">{{ $item->stock }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                @if($item->active)
                                    <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activo</span>
                                @else
                                    <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('admin/menu/items/edit/' . $item->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <form action="{{ url('admin/menu/items/delete/' . $item->id) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar este producto?')"
                                          data-no-loading>
                                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500 italic">
                            No hay productos todavía. Agregá el primero o importalos desde Excel.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('price-form');
    if (!form) return;
    var scope = document.getElementById('pf-scope');
    var sectionWrap = document.getElementById('pf-section-wrap');
    var checks = document.querySelectorAll('.item-check');
    var all = document.getElementById('select-all');

    function selected() { return document.querySelectorAll('.item-check:checked').length; }
    function refresh() {
        var opt = scope.querySelector('option[value="selected"]');
        opt.textContent = 'Productos tildados (' + selected() + ')';
        sectionWrap.classList.toggle('hidden', scope.value !== 'section');
    }
    checks.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (selected() > 0) scope.value = 'selected';
            refresh();
        });
    });
    all.addEventListener('change', function () {
        checks.forEach(function (cb) { cb.checked = all.checked; });
        if (all.checked) scope.value = 'selected';
        refresh();
    });
    scope.addEventListener('change', refresh);

    form.addEventListener('submit', function (e) {
        var pct = parseFloat(String(form.percent.value).replace(',', '.'));
        var n = scope.value === 'all' ? checks.length
              : scope.value === 'selected' ? selected()
              : document.querySelectorAll('tr[data-section="' + form.section_id.value + '"]').length;
        if (scope.value === 'selected' && n === 0) {
            e.preventDefault();
            alert('Tildá al menos un producto de la lista.');
            return;
        }
        var verb = pct > 0 ? 'aumentar un ' + pct : 'bajar un ' + Math.abs(pct);
        if (!confirm('Vas a ' + verb + ' % el precio de ' + n + (n === 1 ? ' producto' : ' productos') + '. ¿Continuar?')) {
            e.preventDefault();
        }
    });
    refresh();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    var list = document.getElementById('sortable-items');
    if (!list) return;

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function () {
            var order = Array.from(list.querySelectorAll('tr[data-id]'))
                             .map(function (r) { return r.dataset.id; });

            fetch('{{ url('admin/menu/items/reorder') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': '{{ $_SESSION['csrf_token'] ?? '' }}' },
                body: JSON.stringify({ order: order })
            }).then(function (r) {
                if (!r.ok) { alert('Error al guardar el orden.'); }
            });
        }
    });
})();
</script>
@endpush
