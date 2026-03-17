@extends('admin/layout')

@section('title', 'Ítems del Menú')
@section('header', 'Ítems del Menú')
@section('subheader', 'Administrá los platos y productos de tu carta. Arrastrá para reordenar.')

@section('content')
<div class="flex justify-end mb-4 gap-3">
    <a href="{{ url('admin/menu/sections') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-layer-group"></i> Secciones
    </a>
    <a href="{{ url('admin/menu/settings') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-gear"></i> Configuración
    </a>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <h3 class="text-lg font-bold text-white">Lista de Ítems</h3>
        <a href="{{ url('admin/menu/items/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo Ítem
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-4 md:py-4 font-bold w-10"></th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-16 hidden sm:table-cell">Imagen</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Título</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">Sección</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Precio</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-items" class="divide-y divide-slate-800/50">
                @if(count($items) > 0)
                    @foreach($items as $item)
                        <tr class="hover:bg-slate-800/20 transition-colors" data-id="{{ $item->id }}">
                            <td class="px-3 py-3 md:px-4 md:py-4">
                                <span class="drag-handle cursor-grab text-slate-600 hover:text-slate-400 transition-colors">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </span>
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
                                @if($item->description)
                                    <p class="text-slate-500 text-xs truncate max-w-[180px]">{{ $item->description }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden md:table-cell">
                                @if($item->section)
                                    <span class="px-2 py-1 bg-blue-500/10 text-blue-400 text-[10px] font-bold rounded-full border border-blue-500/10">{{ $item->section->name }}</span>
                                @else
                                    <span class="text-slate-600 text-xs italic">Sin sección</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                <span class="text-amber-400 font-bold text-sm">{{ $item->price_formatted }}</span>
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
                                          onsubmit="return confirm('¿Eliminar este ítem del menú?')"
                                          data-no-loading>
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
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                            No hay ítems en el menú. ¡Agregá el primero!
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            }).then(function (r) {
                if (!r.ok) { alert('Error al guardar el orden.'); }
            });
        }
    });
})();
</script>
@endpush
