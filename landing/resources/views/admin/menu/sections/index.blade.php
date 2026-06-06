@extends('admin/layout')

@section('title', 'Secciones del Menú')
@section('header', 'Secciones del Menú')
@section('subheader', 'Organiza las categorías de tu carta. Arrastrá para reordenar.')

@section('content')
<div class="flex justify-end mb-4 gap-3">
    <a href="{{ url('admin/menu/items') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-list"></i> Ítems
    </a>
    <a href="{{ url('admin/menu/settings') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-gear"></i> Configuración
    </a>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <h3 class="text-lg font-bold text-white">Lista de Secciones</h3>
        <a href="{{ url('admin/menu/sections/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nueva Sección
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-10"></th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Nombre</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-sections" class="divide-y divide-slate-800/50">
                @if(count($sections) > 0)
                    @foreach($sections as $section)
                        <tr class="hover:bg-slate-800/20 transition-colors" data-id="{{ $section->id }}">
                            <td class="px-3 py-3 md:px-4 md:py-4">
                                <span class="drag-handle cursor-grab text-slate-600 hover:text-slate-400 transition-colors">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </span>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4">
                                <p class="text-white font-bold">{{ $section->name }}</p>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                @if($section->active)
                                    <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activa</span>
                                @else
                                    <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactiva</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('admin/menu/sections/edit/' . $section->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <form action="{{ url('admin/menu/sections/delete/' . $section->id) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar esta sección? Los ítems quedarán sin sección asignada.')"
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
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">
                            No hay secciones creadas. ¡Creá la primera para organizar tu menú!
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
    var list = document.getElementById('sortable-sections');
    if (!list) return;

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function () {
            var order = Array.from(list.querySelectorAll('tr[data-id]'))
                             .map(function (r) { return r.dataset.id; });

            fetch('{{ url('admin/menu/sections/reorder') }}', {
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
