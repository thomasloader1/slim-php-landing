@extends('admin/layout')

@section('title', 'Gestionar Enlaces')
@section('header', 'Enlaces')
@section('subheader', 'Administra los links que aparecen en tu landing page.')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <div>
            <h3 class="text-lg font-bold text-white">Lista de Enlaces</h3>
            <p class="text-xs text-slate-500">Arrastrá <i class="fa-solid fa-grip-vertical"></i> para cambiar el orden en la landing.</p>
        </div>
        @if(can('links.edit'))
        <a href="{{ url('admin/links/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo Enlace
        </a>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-8"><i class="fa-solid fa-grip-vertical"></i></th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Icono</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Título</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">URL</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-links" class="divide-y divide-slate-800/50">
                @if(count($links) > 0)
                    @foreach($links as $link)
                        <tr class="hover:bg-slate-800/20 transition-colors group" data-id="{{ $link->id }}">
                            <td class="px-3 py-3 md:px-6 md:py-4 cursor-grab drag-handle" title="Arrastrar para ordenar" aria-label="Arrastrar para ordenar">
                                <i class="fa-solid fa-grip-vertical text-slate-600"></i>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-slate-800 border border-slate-700 text-lg" style="color: {{ $link->color }}">
                                    {!! $link->getIconHtml() !!}
                                </div>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4">
                                <p class="text-white font-bold">{{ $link->title }}</p>
                                <span class="text-[10px] text-slate-500 uppercase tracking-tighter">{{ $link->type }}</span>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden md:table-cell">
                                <a href="{{ $link->url }}" target="_blank" class="text-slate-400 hover:text-blue-400 text-sm truncate max-w-[200px] block transition-colors">
                                    {{ $link->url }}
                                </a>
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                @if($link->active)
                                    <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activo</span>
                                @else
                                    <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-6 md:py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @if(can('links.edit'))
                                    <a href="{{ url('admin/links/edit/' . $link->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    @endif
                                    @if(can('links.delete'))
                                    <form action="{{ url('admin/links/delete/' . $link->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este enlace?')" data-no-loading>
                                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                            No hay enlaces creados todavía. ¡Crea el primero!
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<style>
    #sortable-links tr.sortable-ghost { opacity: .3; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    var el = document.getElementById('sortable-links');
    if (!el || !el.querySelector('tr[data-id]')) return;
    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function () {
            var order = [];
            el.querySelectorAll('tr[data-id]').forEach(function (row) {
                order.push(parseInt(row.dataset.id, 10));
            });
            fetch('{{ url("admin/links/reorder") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': '{{ $_SESSION['csrf_token'] ?? '' }}' },
                body: JSON.stringify({ order: order })
            }).then(function (r) {
                if (!r.ok) { alert('Error al guardar el orden. Recargá la página e intentá de nuevo.'); }
            });
        }
    });
})();
</script>
@endpush
@endsection
